<?php

namespace App\Services;

use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use App\Enums\StatusPesanan;
use App\Enums\SumberPesanan;
use App\Exceptions\MidtransException;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Logika bisnis pesanan.
 *
 * Menangani pembuatan order tunai oleh kasir (docs/DATABASE.md §7.1) dan
 * order QRIS Pelanggan B yang menunggu pembayaran. Status lunas order QRIS
 * berasal dari webhook atau pemeriksaan status server-side ke Midtrans,
 * bukan dari penandaan manual kasir.
 */
class OrderService
{
    public function __construct(
        private readonly MidtransService $midtrans,
        private readonly QueueNumberService $nomorAntrean,
        private readonly MidtransNotificationService $notifikasi,
    ) {}

    /**
     * Membuat order tunai yang langsung lunas dan mendapat nomor antrean.
     *
     * Seluruh langkah berjalan dalam satu transaksi database: bila salah satu
     * gagal, tidak ada order, item, maupun nomor antrean yang tersimpan.
     * Percobaan diulang maksimal 3 kali bila MySQL melaporkan deadlock saat
     * dua kasir mengambil nomor antrean bersamaan.
     *
     * @param  array{nama_pelanggan: string, tipe_pesanan: string, catatan?: string|null, uang_diterima: int, item: array<int, array{menu_id: int, jumlah: int, catatan?: string|null}>}  $data
     */
    public function buatOrderTunai(array $data, User $kasir): Order
    {
        return DB::transaction(function () use ($data, $kasir): Order {
            $items = $this->siapkanItem($data['item']);
            $total = (int) array_sum(array_column($items, 'subtotal'));
            $uangDiterima = (int) $data['uang_diterima'];

            $this->pastikanUangCukup($uangDiterima, $total);

            $tanggalAntrean = now()->toDateString();

            $order = Order::create([
                'kode_pesanan' => $this->buatKodePesanan(),
                'nomor_antrean' => $this->nomorAntrean->berikutnya($tanggalAntrean),
                'tanggal_antrean' => $tanggalAntrean,
                'nama_pelanggan' => $data['nama_pelanggan'],
                'tipe_pesanan' => $data['tipe_pesanan'],
                'catatan' => $data['catatan'] ?? null,
                'sumber' => SumberPesanan::Kasir,
                'status_pesanan' => StatusPesanan::Antre,
                'metode_pembayaran' => MetodePembayaran::Tunai,
                'status_pembayaran' => StatusPembayaran::Lunas,
                'total' => $total,
                'uang_diterima' => $uangDiterima,
                'kembalian' => $uangDiterima - $total,
                'cashier_id' => $kasir->id,
                'waktu_lunas' => now(),
            ]);

            $order->items()->createMany($items);

            return $order->load('items');
        }, 3);
    }

    /**
     * Membuat order QRIS Pelanggan B yang menunggu pembayaran.
     *
     * Order dan item disimpan lebih dulu dalam satu transaksi database, lalu
     * satu percobaan pembayaran (`{kode_pesanan}-P{n}`) dicatat dan transaksi
     * Snap dibuat. Nomor antrean **tidak** diberikan di sini: nomor baru
     * dibuat setelah webhook menyatakan lunas.
     *
     * Bila Snap gagal, order tetap tersimpan sebagai `menunggu_pembayaran`
     * dan percobaan ditandai `failure` untuk audit. Pelanggan dapat mencoba
     * lagi lewat alur pembayaran ulang pada slice berikutnya.
     *
     * @param  array{nama_pelanggan: string, tipe_pesanan: string, catatan?: string|null, item: array<int, array{menu_id: int, jumlah: int}>}  $data
     *
     * @throws MidtransException
     */
    public function buatOrderQris(array $data): Order
    {
        $order = DB::transaction(function () use ($data): Order {
            $items = $this->siapkanItem($data['item']);
            $total = (int) array_sum(array_column($items, 'subtotal'));

            $order = Order::create([
                'kode_pesanan' => $this->buatKodePesanan(),
                'nama_pelanggan' => $data['nama_pelanggan'],
                'tipe_pesanan' => $data['tipe_pesanan'],
                'catatan' => $data['catatan'] ?? null,
                'sumber' => SumberPesanan::Qr,
                'status_pesanan' => StatusPesanan::MenungguPembayaran,
                'metode_pembayaran' => MetodePembayaran::Qris,
                'status_pembayaran' => StatusPembayaran::Menunggu,
                'total' => $total,
                'batas_waktu_pembayaran' => now()->addMinutes(MidtransService::BATAS_PEMBAYARAN_MENIT),
            ]);

            $order->items()->createMany($items);

            return $order->load('items');
        }, 3);

        $payment = $this->mulaiPercobaanPembayaran($order);

        try {
            $snap = $this->midtrans->buatTransaksiSnap($order, $payment);
        } catch (MidtransException $exception) {
            $this->tandaiPercobaanGagal($payment, $exception);

            throw $exception;
        }

        $this->terapkanResponsSnap($payment, $snap);

        return $order->load('payments');
    }

    /**
     * Percobaan pembayaran yang masih aktif dan belum melewati batas 15 menit.
     */
    public function percobaanAktif(Order $order): ?Payment
    {
        if (in_array($order->status_pembayaran, [
            StatusPembayaran::Lunas,
            StatusPembayaran::Dibatalkan,
            StatusPembayaran::PerluDiperiksa,
        ], true)) {
            return null;
        }

        return $order->payments()
            ->where('status_transaksi', 'pending')
            ->whereNotNull('snap_token')
            ->where('waktu_dibuat', '>', now()->subMinutes(MidtransService::BATAS_PEMBAYARAN_MENIT))
            ->latest('id')
            ->first();
    }

    /**
     * Apakah halaman konfirmasi boleh menampilkan tombol bayar lagi.
     *
     * Percobaan `pending` yang masih aktif membuka token yang sama. Percobaan
     * baru hanya dibuat setelah percobaan terakhir gagal/kedaluwarsa.
     */
    public function dapatBayarUlang(Order $order): bool
    {
        if ($order->nomor_antrean !== null
            || in_array($order->status_pembayaran, [
                StatusPembayaran::Lunas,
                StatusPembayaran::Dibatalkan,
                StatusPembayaran::PerluDiperiksa,
            ], true)
            || in_array($order->status_pesanan, [
                StatusPesanan::Antre,
                StatusPesanan::Diproses,
                StatusPesanan::Selesai,
                StatusPesanan::Dibatalkan,
            ], true)) {
            return false;
        }

        if ($this->percobaanAktif($order)) {
            return true;
        }

        $terakhir = $order->payments()->latest('id')->first();

        if (! $terakhir) {
            return true;
        }

        if ($terakhir->status_transaksi === 'pending') {
            return $terakhir->waktu_dibuat === null
                || $terakhir->waktu_dibuat->lte(now()->subMinutes(MidtransService::BATAS_PEMBAYARAN_MENIT));
        }

        return in_array($terakhir->status_transaksi, [
            'failure',
            'deny',
            'cancel',
            'expire',
            'gagal',
            'kedaluwarsa',
        ], true);
    }

    /**
     * Buka percobaan aktif atau buat percobaan pembayaran berikutnya.
     *
     * Satu percobaan aktif dijaga lewat `lockForUpdate` pada order. Panggilan
     * Snap dilakukan di luar transaksi agar koneksi database tidak tertahan
     * selama request HTTP.
     *
     * @return array{payment: Payment, baru: bool}
     *
     * @throws ValidationException
     * @throws MidtransException
     */
    public function bayarLagi(Order $order): array
    {
        $hasil = DB::transaction(function () use ($order): array {
            $terkunci = Order::query()->lockForUpdate()->findOrFail($order->id);

            if (! $this->dapatBayarUlang($terkunci)) {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Pesanan ini tidak dapat dibayar ulang.',
                ]);
            }

            $aktif = $this->percobaanAktif($terkunci);

            if ($aktif) {
                return ['payment' => $aktif, 'baru' => false];
            }

            // Pending yang sudah lewat 15 menit dianggap kedaluwarsa lokal.
            // Webhook tetap menjadi sumber kebenaran bila notifikasinya datang.
            $terkunci->payments()
                ->where('status_transaksi', 'pending')
                ->where('waktu_dibuat', '<=', now()->subMinutes(MidtransService::BATAS_PEMBAYARAN_MENIT))
                ->update([
                    'status_transaksi' => 'kedaluwarsa',
                    'waktu_diproses' => now(),
                ]);

            $terakhir = $terkunci->payments()->latest('id')->first();

            if ($terakhir && $terakhir->status_transaksi === 'pending') {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Pembayaran sedang dibuat. Coba lagi beberapa detik lagi.',
                ]);
            }

            if ($terakhir && ! in_array($terakhir->status_transaksi, [
                'failure',
                'deny',
                'cancel',
                'expire',
                'gagal',
                'kedaluwarsa',
            ], true)) {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Status pembayaran terakhir belum bisa dibuat ulang.',
                ]);
            }

            $terkunci->update([
                'status_pembayaran' => StatusPembayaran::Menunggu,
                'batas_waktu_pembayaran' => now()->addMinutes(MidtransService::BATAS_PEMBAYARAN_MENIT),
            ]);

            $payment = $this->mulaiPercobaanPembayaran($terkunci);

            return ['payment' => $payment, 'baru' => true];
        }, 3);

        if (! $hasil['baru']) {
            return $hasil;
        }

        $payment = $hasil['payment'];

        try {
            $snap = $this->midtrans->buatTransaksiSnap($order->load('items'), $payment);
        } catch (MidtransException $exception) {
            $this->tandaiPercobaanGagal($payment, $exception);

            throw $exception;
        }

        $this->terapkanResponsSnap($payment, $snap);

        return ['payment' => $payment->refresh(), 'baru' => true];
    }

    /**
     * Batalkan pesanan QRIS yang belum dibayar.
     *
     * Pembatalan lokal hanya dilakukan setelah Midtrans mengonfirmasi
     * transaksi aktif sudah `cancel`/`expire`, atau bila percobaan terakhir
     * memang tidak aktif lagi. Bila Midtrans tidak bisa dihubungi, order
     * tetap `menunggu` (PRD §8.4.9).
     *
     * @throws ValidationException
     * @throws MidtransException
     */
    public function batalkanPesananQris(Order $order): Order
    {
        $awal = DB::transaction(function () use ($order): array {
            $terkunci = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($this->sudahDibatalkan($terkunci)) {
                return [
                    'order' => $terkunci->load(['items', 'payments']),
                    'payment' => null,
                ];
            }

            $this->pastikanDapatDibatalkan($terkunci);

            $terakhir = $terkunci->payments()->latest('id')->first();

            if (! $terakhir) {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Pembayaran QRIS belum tercatat. Coba lagi beberapa saat lagi.',
                ]);
            }

            // Transaksi yang sudah settlement tidak boleh dibatalkan lokal.
            // `capture` juga ditolak karena fraud_status belum diputuskan.
            if (in_array($terakhir->status_transaksi, ['settlement', 'capture'], true)) {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Pembayaran sudah lunas atau sedang diverifikasi; pesanan tidak dapat dibatalkan.',
                ]);
            }

            return ['order' => $terkunci, 'payment' => $terakhir];
        }, 3);

        if ($awal['order']->status_pembayaran === StatusPembayaran::Dibatalkan
            || $awal['order']->status_pesanan === StatusPesanan::Dibatalkan) {
            return $awal['order'];
        }

        /** @var Payment $payment */
        $payment = $awal['payment'];

        $payload = $payment->status_transaksi === 'pending'
            ? $this->batalkanDiMidtrans($payment, $awal['order'])
            : $this->payloadStatusLokal($payment);

        // Terapkan status Midtrans lebih dulu (mis. cancel/expire). Bila
        // ternyata settlement, order sudah menjadi lunas dan langkah final
        // akan menolak pembatalan lokal.
        $this->notifikasi->terapkanStatus($payment, $payload);

        return DB::transaction(function () use ($order): Order {
            $terkunci = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($this->sudahDibatalkan($terkunci)) {
                return $terkunci->refresh()->load(['items', 'payments']);
            }

            if ($terkunci->status_pembayaran === StatusPembayaran::Lunas
                || $terkunci->status_pembayaran === StatusPembayaran::PerluDiperiksa
                || $terkunci->nomor_antrean !== null
                || in_array($terkunci->status_pesanan, [
                    StatusPesanan::Antre,
                    StatusPesanan::Diproses,
                    StatusPesanan::Selesai,
                ], true)) {
                throw ValidationException::withMessages([
                    'pembayaran' => "Status pembayaran berubah menjadi {$terkunci->status_pembayaran->label()}; pesanan tidak dibatalkan.",
                ]);
            }

            if (! in_array($terkunci->status_pembayaran, [
                StatusPembayaran::Menunggu,
                StatusPembayaran::Gagal,
                StatusPembayaran::Kedaluwarsa,
            ], true)) {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Status pembayaran terakhir belum dapat dibatalkan.',
                ]);
            }

            $terkunci->update([
                'status_pembayaran' => StatusPembayaran::Dibatalkan,
                'status_pesanan' => StatusPesanan::Dibatalkan,
            ]);

            return $terkunci->refresh()->load(['items', 'payments']);
        }, 3);
    }

    /**
     * Cek status pembayaran langsung ke API Midtrans.
     *
     * Status hasil pemeriksaan diproses lewat service notifikasi yang sama,
     * sehingga `settlement` tetap membuat nomor antrean secara idempoten dan
     * `settlement` setelah pembatalan lokal menjadi `perlu_diperiksa`.
     *
     * @throws ValidationException
     * @throws MidtransException
     */
    public function periksaStatusPembayaran(Order $order): Order
    {
        $payment = DB::transaction(function () use ($order): Payment {
            $terkunci = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($terkunci->sumber !== SumberPesanan::Qr
                || $terkunci->metode_pembayaran !== MetodePembayaran::Qris) {
                throw ValidationException::withMessages([
                    'pesanan' => 'Cek status hanya tersedia untuk pesanan QRIS.',
                ]);
            }

            $terakhir = $terkunci->payments()->latest('id')->first();

            if (! $terakhir) {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Belum ada percobaan pembayaran QRIS untuk diperiksa.',
                ]);
            }

            return $terakhir;
        }, 3);

        $payload = $this->midtrans->ambilStatus($payment, $order);

        return $this->notifikasi->terapkanStatus($payment, $payload);
    }

    /**
     * Payload sintetis untuk percobaan yang sudah terminal secara lokal.
     *
     * @return array<string, mixed>
     */
    private function payloadStatusLokal(Payment $payment): array
    {
        return [
            'order_id' => $payment->midtrans_order_id,
            'transaction_id' => $payment->transaction_id,
            'transaction_status' => $payment->status_transaksi,
            'status_code' => $payment->status_code ?? '200',
            'fraud_status' => $payment->fraud_status,
            'gross_amount' => $payment->gross_amount,
            'payment_type' => $payment->payment_type,
        ];
    }

    /**
     * Minta Midtrans membatalkan transaksi, lalu periksa status bila ditolak.
     *
     * @return array<string, mixed>
     *
     * @throws ValidationException
     * @throws MidtransException
     */
    private function batalkanDiMidtrans(Payment $payment, Order $order): array
    {
        try {
            $payload = $this->midtrans->batalkanTransaksi($payment, $order);

            $status = (string) ($payload['transaction_status'] ?? '');

            // Jangan pernah membatalkan lokal hanya karena HTTP 200. Status
            // harus terminal, atau settlement/capture agar kegagalan bayar
            // bisa ditangani sebagai konflik oleh langkah final.
            if (! in_array($status, [
                'cancel',
                'expire',
                'deny',
                'failure',
                'gagal',
                'kedaluwarsa',
                'settlement',
                'capture',
            ], true)) {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Midtrans belum mengonfirmasi pembatalan; status pesanan tetap menunggu.',
                ]);
            }

            return $payload;
        } catch (MidtransException $pembatalan) {
            try {
                $status = $this->midtrans->ambilStatus($payment, $order);
            } catch (MidtransException) {
                // Pembatalan dan pemeriksaan status sama-sama gagal: biarkan
                // order menunggu agar tidak ada pembatalan tanpa konfirmasi.
                throw $pembatalan;
            }

            if ($status['transaction_status'] === 'pending') {
                throw ValidationException::withMessages([
                    'pembayaran' => 'Midtrans belum mengonfirmasi pembatalan; status pesanan tetap menunggu.',
                ]);
            }

            return $status;
        }
    }

    /**
     * Pesanan dianggap sudah dibatalkan bila salah satu status finalnya sudah
     * `dibatalkan`. Dipakai agar klik ganda bersifat idempoten.
     */
    private function sudahDibatalkan(Order $order): bool
    {
        return $order->status_pembayaran === StatusPembayaran::Dibatalkan
            || $order->status_pesanan === StatusPesanan::Dibatalkan;
    }

    /**
     * Tolak pembatalan untuk pesanan lunas, perlu diperiksa, atau bukan QRIS.
     *
     * @throws ValidationException
     */
    private function pastikanDapatDibatalkan(Order $order): void
    {
        if ($order->sumber !== SumberPesanan::Qr
            || $order->metode_pembayaran !== MetodePembayaran::Qris) {
            throw ValidationException::withMessages([
                'pesanan' => 'Hanya pesanan QRIS yang belum dibayar yang dapat dibatalkan.',
            ]);
        }

        if ($order->status_pembayaran === StatusPembayaran::Lunas
            || $order->status_pembayaran === StatusPembayaran::PerluDiperiksa
            || $order->nomor_antrean !== null
            || in_array($order->status_pesanan, [
                StatusPesanan::Antre,
                StatusPesanan::Diproses,
                StatusPesanan::Selesai,
            ], true)) {
            throw ValidationException::withMessages([
                'pesanan' => 'Pesanan yang sudah lunas atau perlu diperiksa tidak dapat dibatalkan.',
            ]);
        }

        if ($order->status_pesanan !== StatusPesanan::MenungguPembayaran) {
            throw ValidationException::withMessages([
                'pesanan' => 'Pesanan ini tidak berada pada status menunggu pembayaran.',
            ]);
        }
    }

    /**
     * Simpan token/redirect dan respons mentah Snap pada baris percobaan.
     *
     * @param  array<string, mixed>  $snap
     */
    private function terapkanResponsSnap(Payment $payment, array $snap): void
    {
        $payment->update([
            'transaction_id' => $snap['transaction_id'] ?? null,
            'status_transaksi' => $snap['transaction_status'] ?? 'pending',
            'status_code' => (string) ($snap['status_code'] ?? '201'),
            'payment_type' => $snap['payment_type'] ?? null,
            'snap_token' => $snap['token'],
            'snap_redirect_url' => $snap['redirect_url'],
            'raw_response' => $snap,
        ]);
    }

    /**
     * Catat percobaan pembayaran baru sebelum memanggil Midtrans.
     *
     * Nomor percobaan dihitung dari jumlah baris `payments` order, sehingga
     * percobaan yang gagal tetap memakai nomor `-P{n}` dan tidak dipakai ulang
     * saat pelanggan membayar ulang (PRD §8.4.4).
     */
    private function mulaiPercobaanPembayaran(Order $order): Payment
    {
        $nomorPercobaan = $order->payments()->count() + 1;

        return $order->payments()->create([
            'midtrans_order_id' => $order->kode_pesanan.'-P'.$nomorPercobaan,
            'status_transaksi' => 'pending',
            'status_code' => '201',
            'gross_amount' => number_format($order->total, 2, '.', ''),
            'waktu_dibuat' => now(),
        ]);
    }

    /**
     * Tandai percobaan yang gagal dibuat di Midtrans agar tidak dianggap aktif.
     *
     * Status order tidak diubah: order masih `menunggu` dan bisa dibayar lagi.
     */
    private function tandaiPercobaanGagal(Payment $payment, MidtransException $exception): void
    {
        $payment->update([
            'status_transaksi' => 'failure',
            'status_code' => $exception->statusCode ?? '500',
            'raw_response' => $exception->rawResponse,
            'waktu_diproses' => now(),
        ]);
    }

    /**
     * Ubah status pesanan mengikuti alur antre → diproses → selesai.
     *
     * Baris order dikunci agar dua kasir tidak mengubah status bersamaan.
     *
     * @throws ValidationException
     */
    public function ubahStatus(Order $order, StatusPesanan $tujuan): Order
    {
        return DB::transaction(function () use ($order, $tujuan): Order {
            $terkunci = Order::query()->lockForUpdate()->findOrFail($order->id);
            $asal = $terkunci->status_pesanan;

            if ($tujuan !== ($this->transisiBerikutnya($asal))) {
                throw ValidationException::withMessages([
                    'status' => "Status pesanan tidak bisa diubah dari {$asal->label()} ke {$tujuan->label()}.",
                ]);
            }

            $terkunci->status_pesanan = $tujuan;
            $terkunci->save();

            return $terkunci->load('items');
        });
    }

    /**
     * Status berikutnya yang diizinkan, atau null bila sudah final.
     */
    private function transisiBerikutnya(StatusPesanan $status): ?StatusPesanan
    {
        return match ($status) {
            StatusPesanan::Antre => StatusPesanan::Diproses,
            StatusPesanan::Diproses => StatusPesanan::Selesai,
            default => null,
        };
    }

    /**
     * Validasi ulang menu di server dan susun snapshot order_items.
     *
     * Harga diambil dari database, bukan dari browser. Menu yang sudah
     * di-soft-delete otomatis tidak ikut karena scope SoftDeletes.
     *
     * @param  array<int, array{menu_id: int, jumlah: int, catatan?: string|null}>  $baris
     * @return array<int, array<string, mixed>>
     */
    private function siapkanItem(array $baris): array
    {
        /** @var Collection<int, Menu> $menu */
        $menu = Menu::query()
            ->whereIn('id', array_column($baris, 'menu_id'))
            ->get()
            ->keyBy('id');

        $hasil = [];

        foreach ($baris as $item) {
            $data = $menu->get($item['menu_id']);

            if (! $data) {
                throw ValidationException::withMessages([
                    'item' => 'Ada menu yang sudah tidak tersedia. Muat ulang halaman lalu coba lagi.',
                ]);
            }

            if (! $data->tersedia) {
                throw ValidationException::withMessages([
                    'item' => "Menu {$data->nama} sedang habis. Hapus dari pesanan untuk melanjutkan.",
                ]);
            }

            $jumlah = (int) $item['jumlah'];

            $hasil[] = [
                'menu_id' => $data->id,
                'nama_menu' => $data->nama,
                'harga_satuan' => $data->harga,
                'jumlah' => $jumlah,
                'subtotal' => $data->harga * $jumlah,
                'catatan' => $item['catatan'] ?? null,
            ];
        }

        return $hasil;
    }

    /**
     * Uang tunai harus menutup total tagihan.
     *
     * @throws ValidationException
     */
    private function pastikanUangCukup(int $uangDiterima, int $total): void
    {
        if ($uangDiterima >= $total) {
            return;
        }

        throw ValidationException::withMessages([
            'uang_diterima' => 'Uang diterima kurang '.$this->rupiah($total - $uangDiterima).' dari total tagihan.',
        ]);
    }

    /**
     * Kode pesanan publik: 8 karakter alfanumerik kapital.
     */
    private function buatKodePesanan(): string
    {
        do {
            $kode = Str::upper(Str::random(8));
        } while (Order::query()->where('kode_pesanan', $kode)->exists());

        return $kode;
    }

    private function rupiah(int $nilai): string
    {
        return 'Rp '.number_format($nilai, 0, ',', '.');
    }
}

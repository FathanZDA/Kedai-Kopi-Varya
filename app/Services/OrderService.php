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
use App\Models\QueueCounter;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Logika bisnis pesanan.
 *
 * Menangani pembuatan order tunai oleh kasir (docs/DATABASE.md §7.1) dan
 * order QRIS Pelanggan B yang menunggu pembayaran. Webhook Midtrans belum ada,
 * sehingga status lunas tidak pernah ditentukan dari sini.
 */
class OrderService
{
    public function __construct(private readonly MidtransService $midtrans) {}

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

            $order = Order::create([
                'kode_pesanan' => $this->buatKodePesanan(),
                'nomor_antrean' => $this->ambilNomorAntrean(),
                'tanggal_antrean' => now()->toDateString(),
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

        $payment->update([
            'transaction_id' => $snap['transaction_id'] ?? null,
            'status_transaksi' => $snap['transaction_status'] ?? 'pending',
            'status_code' => (string) ($snap['status_code'] ?? '201'),
            'payment_type' => $snap['payment_type'] ?? null,
            'snap_token' => $snap['token'],
            'snap_redirect_url' => $snap['redirect_url'],
            'raw_response' => $snap,
        ]);

        return $order->load('payments');
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
     * Ambil nomor antrean berikutnya untuk hari ini.
     *
     * Baris penghitung dikunci dengan lockForUpdate() agar dua transaksi
     * bersamaan tidak mendapat nomor yang sama. insertOrIgnore menangani
     * hari pertama saat baris belum ada.
     */
    private function ambilNomorAntrean(): int
    {
        $tanggal = now()->toDateString();

        QueueCounter::query()->insertOrIgnore([
            'tanggal' => $tanggal,
            'nomor_terakhir' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $counter = QueueCounter::query()
            ->where('tanggal', $tanggal)
            ->lockForUpdate()
            ->firstOrFail();

        $counter->nomor_terakhir += 1;
        $counter->save();

        return $counter->nomor_terakhir;
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

<?php

namespace App\Services;

use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use App\Enums\StatusPesanan;
use App\Enums\SumberPesanan;
use App\Models\Menu;
use App\Models\Order;
use App\Models\QueueCounter;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Logika bisnis pesanan.
 *
 * Slice ini hanya menangani pembuatan order tunai oleh kasir sesuai
 * docs/DATABASE.md §7.1. Order QRIS dan webhook Midtrans belum ada.
 */
class OrderService
{
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

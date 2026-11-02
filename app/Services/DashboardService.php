<?php

namespace App\Services;

use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use App\Enums\StatusPesanan;
use App\Models\Menu;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonImmutable;

/**
 * Ringkasan Dashboard admin (PRD AD-01, `admin.md` §A).
 *
 * Semua angka dihitung dari `orders`, `order_items`, dan `payments`:
 * - Pendapatan hari ini dan bulan berjalan hanya dari transaksi `lunas`.
 * - Pesanan menunggu pembayaran dan pesanan diproses dihitung untuk hari
 *   berjalan, sama dengan cakupan halaman Pesanan Masuk kasir.
 * - Menu terlaris memakai snapshot `order_items` dari transaksi `lunas`
 *   bulan berjalan.
 * - Transaksi terbaru tidak difilter status, hanya urutan waktu.
 */
class DashboardService
{
    private const ZONA = 'Asia/Jakarta';

    private const BATAS_TRANSAKSI = 10;

    private const BATAS_MENU = 5;

    /**
     * Seluruh data yang dibutuhkan halaman dashboard.
     *
     * @return array<string, mixed>
     */
    public function data(): array
    {
        $sekarang = CarbonImmutable::now(self::ZONA);
        $hariIni = [$sekarang->startOfDay(), $sekarang->endOfDay()];
        $kemarin = $sekarang->subDay();
        $kemarinRange = [$kemarin->startOfDay(), $kemarin->endOfDay()];
        $bulanIni = [$sekarang->startOfMonth(), $sekarang->endOfMonth()];

        $lunasBulan = Order::query()
            ->where('status_pembayaran', StatusPembayaran::Lunas->value)
            ->whereBetween('created_at', $bulanIni);

        $pendapatanBulan = (int) (clone $lunasBulan)->sum('total');
        $transaksiLunasBulan = (clone $lunasBulan)->count();
        $qrisBulan = (int) (clone $lunasBulan)
            ->where('metode_pembayaran', MetodePembayaran::Qris->value)
            ->sum('total');
        $tunaiBulan = $pendapatanBulan - $qrisBulan;

        $pesananHariIni = Order::query()->whereBetween('created_at', $hariIni)->count();
        $pesananKemarin = Order::query()->whereBetween('created_at', $kemarinRange)->count();

        return [
            'today_label' => $sekarang->locale('id')->translatedFormat('l, j F Y').' // '.$sekarang->format('H:i').' WIB',
            'month_label' => $sekarang->locale('id')->translatedFormat('F Y'),
            'today_revenue' => (int) Order::query()
                ->where('status_pembayaran', StatusPembayaran::Lunas->value)
                ->whereBetween('created_at', $hariIni)
                ->sum('total'),
            'month_revenue' => $pendapatanBulan,
            'month_count' => $transaksiLunasBulan,
            'month_qris' => $qrisBulan,
            'month_cash' => $tunaiBulan,
            'qris_percent' => $pendapatanBulan > 0 ? round(($qrisBulan / $pendapatanBulan) * 100, 1) : 0,
            'cash_percent' => $pendapatanBulan > 0 ? round(($tunaiBulan / $pendapatanBulan) * 100, 1) : 0,
            'today_orders' => $pesananHariIni,
            'yesterday_orders' => $pesananKemarin,
            'orders_change_percent' => $pesananKemarin > 0
                ? round((($pesananHariIni - $pesananKemarin) / $pesananKemarin) * 100, 1)
                : null,
            'pending_payments' => Order::query()
                ->whereBetween('created_at', $hariIni)
                ->where('status_pembayaran', StatusPembayaran::Menunggu->value)
                ->count(),
            'processing' => Order::query()
                ->whereBetween('created_at', $hariIni)
                ->where('status_pesanan', StatusPesanan::Diproses->value)
                ->count(),
            'popular_menus' => $this->menuTerlaris($bulanIni),
            'recent_orders' => $this->transaksiTerbaru(),
        ];
    }

    /**
     * Lima menu terlaris bulan berjalan berdasarkan jumlah item terjual pada
     * transaksi lunas. Nama, harga, dan foto mengikuti data menu terkini;
     * jumlah terjual tetap dari snapshot `order_items`.
     *
     * @param  array{0: CarbonImmutable, 1: CarbonImmutable}  $rentang
     * @return list<array<string, mixed>>
     */
    private function menuTerlaris(array $rentang): array
    {
        $baris = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status_pembayaran', StatusPembayaran::Lunas->value)
            ->whereBetween('orders.created_at', $rentang)
            ->selectRaw('order_items.nama_menu as nama, SUM(order_items.jumlah) as terjual')
            ->groupBy('order_items.nama_menu')
            ->orderByDesc('terjual')
            ->orderBy('order_items.nama_menu')
            ->limit(self::BATAS_MENU)
            ->get();

        $menus = Menu::query()
            ->withTrashed()
            ->with('category')
            ->whereIn('nama', $baris->pluck('nama')->all())
            ->get()
            ->keyBy('nama');

        return $baris->map(function (OrderItem $item) use ($menus): array {
            $menu = $menus->get($item->nama);

            return [
                'name' => $item->nama,
                'price' => $menu?->harga ?? 0,
                'note' => $menu?->category?->nama ?? 'Menu',
                'sales' => ((int) $item->terjual).'x terjual',
                'tag' => 'Bulan Ini',
                'image' => $menu?->foto ? asset($menu->foto) : asset('images/menus/varya-signature.jpg'),
            ];
        })->all();
    }

    /**
     * Sepuluh transaksi terbaru tanpa filter status, lengkap dengan data yang
     * dipakai tabel dashboard.
     *
     * @return list<array<string, mixed>>
     */
    private function transaksiTerbaru(): array
    {
        return Order::query()
            ->with(['items', 'payments'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(self::BATAS_TRANSAKSI)
            ->get()
            ->map(function (Order $order): array {
                $pembayaran = $order->payments->sortByDesc('id')->first();

                return [
                    'id' => $order->id,
                    'code' => $order->kode_pesanan,
                    'queue' => $order->nomor_antrean === null
                        ? null
                        : str_pad((string) $order->nomor_antrean, 3, '0', STR_PAD_LEFT),
                    'customer' => $order->nama_pelanggan,
                    'type' => $order->tipe_pesanan->value,
                    'type_label' => $order->tipe_pesanan->label(),
                    'items' => $order->items->count(),
                    'portions' => (int) $order->items->sum('jumlah'),
                    'total' => $order->total,
                    'status' => $order->status_pesanan->value,
                    'status_label' => $order->status_pesanan->label(),
                    'payment' => $order->metode_pembayaran->label(),
                    'payment_status' => $order->status_pembayaran->value,
                    'payment_status_label' => $order->status_pembayaran->label(),
                    'midtrans_id' => $pembayaran?->midtrans_order_id,
                    'time' => $order->created_at
                        ? $order->created_at->locale('id')->diffForHumans()
                        : '-',
                    'datetime' => $order->created_at
                        ? $order->created_at->locale('id')->translatedFormat('d M Y, H:i').' WIB'
                        : '-',
                ];
            })
            ->all();
    }
}

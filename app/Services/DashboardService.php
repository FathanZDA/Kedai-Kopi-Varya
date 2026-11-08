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
 * - Pendapatan periode aktif hanya dari transaksi `lunas`.
 * - Periode ringkasan: hari ini (default), minggu ini (Senin-Minggu), atau
 *   bulan ini. Nilai `periode` tidak valid jatuh ke `hari`.
 * - Pesanan menunggu pembayaran dan pesanan diproses tetap dihitung untuk
 *   hari berjalan, sama dengan cakupan halaman Pesanan Masuk kasir.
 * - Menu terlaris memakai snapshot `order_items` dari transaksi `lunas`
 *   bulan berjalan.
 * - Transaksi terbaru tidak difilter status maupun periode, hanya urutan
 *   waktu.
 */
class DashboardService
{
    private const ZONA = 'Asia/Jakarta';

    private const BATAS_TRANSAKSI = 10;

    private const BATAS_MENU = 5;

    /** @var list<string> */
    private const PERIODE = ['hari', 'minggu', 'bulan'];

    /**
     * Seluruh data yang dibutuhkan halaman dashboard.
     *
     * @return array<string, mixed>
     */
    public function data(string $periode = 'hari'): array
    {
        $sekarang = CarbonImmutable::now(self::ZONA);
        $periode = $this->periodeValid($periode);
        $hariIni = [$sekarang->startOfDay(), $sekarang->endOfDay()];
        $bulanIni = [$sekarang->startOfMonth(), $sekarang->endOfMonth()];

        $ringkasanHari = $this->ringkasanPada('hari', $sekarang);
        $ringkasanMinggu = $this->ringkasanPada('minggu', $sekarang);
        $ringkasanBulan = $this->ringkasanPada('bulan', $sekarang);
        $ringkasanAktif = match ($periode) {
            'minggu' => $ringkasanMinggu,
            'bulan' => $ringkasanBulan,
            default => $ringkasanHari,
        };

        return [
            'today_label' => $sekarang->locale('id')->translatedFormat('l, j F Y').' // '.$sekarang->format('H:i').' WIB',
            'month_label' => $sekarang->locale('id')->translatedFormat('F Y'),
            'today_revenue' => $ringkasanHari['revenue'],
            'month_revenue' => $ringkasanBulan['revenue'],
            'month_count' => $ringkasanBulan['count'],
            'month_qris' => $ringkasanBulan['qris'],
            'month_cash' => $ringkasanBulan['tunai'],
            'qris_percent' => $ringkasanBulan['qris_percent'],
            'cash_percent' => $ringkasanBulan['cash_percent'],
            'today_orders' => $ringkasanHari['orders'],
            'yesterday_orders' => $ringkasanHari['pembanding'],
            'orders_change_percent' => $ringkasanHari['change_percent'],
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
            'periode' => $periode,
            'ringkasan' => $ringkasanAktif,
            'ringkasan_periode' => [
                'hari' => $ringkasanHari,
                'minggu' => $ringkasanMinggu,
                'bulan' => $ringkasanBulan,
            ],
        ];
    }

    /**
     * Periode dashboard yang sah. Nilai kosong/tidak dikenal menjadi `hari`.
     */
    public function periodeValid(mixed $periode): string
    {
        return is_string($periode) && in_array($periode, self::PERIODE, true)
            ? $periode
            : 'hari';
    }

    /**
     * Ringkasan satu periode. Minggu memakai Senin sebagai hari pertama.
     *
     * @return array<string, mixed>
     */
    public function ringkasan(string $periode): array
    {
        return $this->ringkasanPada(
            $this->periodeValid($periode),
            CarbonImmutable::now(self::ZONA),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function ringkasanPada(string $periode, CarbonImmutable $sekarang): array
    {
        [$mulai, $akhir, $mulaiPembanding, $akhirPembanding, $pembandingLabel] = $this->rentangPeriode($periode, $sekarang);

        $lunas = Order::query()
            ->where('status_pembayaran', StatusPembayaran::Lunas->value)
            ->whereBetween('created_at', [$mulai, $akhir]);

        $revenue = (int) (clone $lunas)->sum('total');
        $count = (clone $lunas)->count();
        $qris = (int) (clone $lunas)
            ->where('metode_pembayaran', MetodePembayaran::Qris->value)
            ->sum('total');
        $tunai = $revenue - $qris;

        $orders = Order::query()
            ->whereBetween('created_at', [$mulai, $akhir])
            ->count();
        $pembanding = Order::query()
            ->whereBetween('created_at', [$mulaiPembanding, $akhirPembanding])
            ->count();

        return [
            'periode' => $periode,
            'label' => $this->labelPeriode($periode),
            'revenue' => $revenue,
            'count' => $count,
            'qris' => $qris,
            'tunai' => $tunai,
            'qris_percent' => $revenue > 0 ? round(($qris / $revenue) * 100, 1) : 0,
            'cash_percent' => $revenue > 0 ? round(($tunai / $revenue) * 100, 1) : 0,
            'orders' => $orders,
            'pembanding' => $pembanding,
            'pembanding_label' => $pembandingLabel,
            'change_percent' => $pembanding > 0
                ? round((($orders - $pembanding) / $pembanding) * 100, 1)
                : null,
        ];
    }

    private function labelPeriode(string $periode): string
    {
        return match ($periode) {
            'minggu' => 'Minggu Ini',
            'bulan' => 'Bulan Ini',
            default => 'Hari Ini',
        };
    }

    /**
     * Rentang periode aktif dan periode pembandingnya.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable, 2: CarbonImmutable, 3: CarbonImmutable, 4: string}
     */
    private function rentangPeriode(string $periode, CarbonImmutable $sekarang): array
    {
        return match ($periode) {
            'minggu' => [
                $sekarang->startOfWeek(CarbonImmutable::MONDAY),
                $sekarang,
                $sekarang->startOfWeek(CarbonImmutable::MONDAY)->subWeek(),
                $sekarang->startOfWeek(CarbonImmutable::MONDAY)->subWeek()->endOfWeek(),
                'minggu lalu',
            ],
            'bulan' => [
                $sekarang->startOfMonth(),
                $sekarang->endOfMonth(),
                $sekarang->startOfMonth()->subMonth(),
                $sekarang->startOfMonth()->subMonth()->endOfMonth(),
                'bulan lalu',
            ],
            default => [
                $sekarang->startOfDay(),
                $sekarang->endOfDay(),
                $sekarang->subDay()->startOfDay(),
                $sekarang->subDay()->endOfDay(),
                'kemarin',
            ],
        };
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

<?php

namespace App\Services;

use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use App\Models\Order;
use Carbon\CarbonImmutable;

/**
 * Laporan transaksi admin (PRD AD-03, §8.8).
 *
 * Sumber data sama dengan pesanan kasir: `orders`, `order_items`, dan
 * `payments`. Hanya `status_pembayaran = lunas` yang dihitung sebagai
 * pendapatan; status lain tetap ditampilkan sebagai informasi.
 */
class ReportService
{
    private const ZONA = 'Asia/Jakarta';

    private const MAKS_BULAN = 36;

    /**
     * Bulan laporan yang dipakai halaman. Format `YYYY-MM`; nilai tidak valid
     * jatuh ke bulan berjalan agar URL rusak tidak menghasilkan error.
     */
    public function bulanValid(?string $bulan): string
    {
        if (is_string($bulan) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $bulan) === 1) {
            return $bulan;
        }

        return CarbonImmutable::now(self::ZONA)->format('Y-m');
    }

    /**
     * Pilihan bulan untuk filter, terbaru lebih dulu. Bulan berjalan selalu ada
     * walau belum punya transaksi; bulan yang sedang dipilih juga dipertahankan.
     *
     * @return list<array{value: string, label: string}>
     */
    public function daftarBulan(string $terpilih): array
    {
        $sekarang = CarbonImmutable::now(self::ZONA)->startOfMonth();
        $palingAwal = Order::query()->min('created_at');

        $mulai = $palingAwal
            ? CarbonImmutable::parse((string) $palingAwal, self::ZONA)->startOfMonth()
            : $sekarang;

        if ($mulai->greaterThan($sekarang)) {
            $mulai = $sekarang;
        }

        $batas = $sekarang->subMonths(self::MAKS_BULAN - 1);

        if ($mulai->lessThan($batas)) {
            $mulai = $batas;
        }

        $nilai = [];

        for ($kursor = $sekarang; $kursor->greaterThanOrEqualTo($mulai); $kursor = $kursor->subMonth()) {
            $nilai[] = $kursor->format('Y-m');
        }

        if (! in_array($terpilih, $nilai, true)) {
            $nilai[] = $terpilih;
            rsort($nilai);
        }

        return array_map(fn (string $bulan): array => [
            'value' => $bulan,
            'label' => $this->labelBulan($bulan),
        ], $nilai);
    }

    /**
     * Transaksi pada satu bulan, terbaru lebih dulu. Semua status pembayaran
     * ikut ditampilkan; perhitungan pendapatan dilakukan di `ringkasan()`.
     *
     * @return list<array<string, mixed>>
     */
    public function transaksi(string $bulan): array
    {
        [$mulai, $akhir] = $this->rentang($bulan);

        return Order::query()
            ->with(['items', 'payments'])
            ->whereBetween('created_at', [$mulai, $akhir])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Order $order): array => $this->baris($order))
            ->all();
    }

    /**
     * Ringkasan pendapatan bulan terpilih. Hanya transaksi `lunas` yang
     * dihitung; persentase memakai total pendapatan sebagai penyebut.
     *
     * @return array<string, mixed>
     */
    public function ringkasan(string $bulan): array
    {
        [$mulai, $akhir] = $this->rentang($bulan);
        [$awalSebelumnya, $akhirSebelumnya] = $this->rentang(
            CarbonImmutable::createFromFormat('!Y-m', $bulan, self::ZONA)->subMonth()->format('Y-m')
        );

        $lunas = Order::query()
            ->where('status_pembayaran', StatusPembayaran::Lunas->value)
            ->whereBetween('created_at', [$mulai, $akhir]);

        $revenue = (int) (clone $lunas)->sum('total');
        $count = (clone $lunas)->count();
        $qris = (int) (clone $lunas)->where('metode_pembayaran', MetodePembayaran::Qris->value)->sum('total');
        $cash = $revenue - $qris;

        $sebelumnya = (int) Order::query()
            ->where('status_pembayaran', StatusPembayaran::Lunas->value)
            ->whereBetween('created_at', [$awalSebelumnya, $akhirSebelumnya])
            ->sum('total');

        return [
            'revenue' => $revenue,
            'count' => $count,
            'qris' => $qris,
            'cash' => $cash,
            'average' => $count > 0 ? (int) round($revenue / $count) : 0,
            'qris_percent' => $revenue > 0 ? round(($qris / $revenue) * 100, 1) : 0,
            'cash_percent' => $revenue > 0 ? round(($cash / $revenue) * 100, 1) : 0,
            'previous_revenue' => $sebelumnya,
            'change_percent' => $sebelumnya > 0
                ? round((($revenue - $sebelumnya) / $sebelumnya) * 100, 1)
                : null,
            'month_label' => $this->labelBulan($bulan),
        ];
    }

    /**
     * Rincian satu transaksi untuk dialog laporan. Item memakai snapshot
     * `order_items`, bukan data menu terkini.
     *
     * @return array<string, mixed>
     */
    public function detail(Order $order): array
    {
        $order->loadMissing(['items', 'payments']);

        $pembayaran = $order->payments->sortByDesc('id')->first();

        return [
            'id' => $order->id,
            'code' => $order->kode_pesanan,
            'queue' => $this->nomorAntrean($order),
            'customer' => $order->nama_pelanggan,
            'tipe_pesanan' => $order->tipe_pesanan->value,
            'tipe_pesanan_label' => $order->tipe_pesanan->label(),
            'source' => $order->sumber->value,
            'source_label' => $order->sumber->label(),
            'datetime' => $this->waktu($order),
            'status' => $order->status_pembayaran->value,
            'status_label' => $order->status_pembayaran->label(),
            'method' => $order->metode_pembayaran->value,
            'method_label' => $order->metode_pembayaran->label(),
            'total' => $order->total,
            'cash_received' => $order->uang_diterima,
            'change' => $order->kembalian,
            'items' => $order->items->map(fn ($item): array => [
                'name' => $item->nama_menu,
                'qty' => $item->jumlah,
                'price' => $item->harga_satuan,
                'subtotal' => $item->subtotal,
                'catatan' => $item->catatan,
            ])->values()->all(),
            'midtrans_order_id' => $pembayaran?->midtrans_order_id,
            'midtrans_transaction_id' => $pembayaran?->transaction_id,
            'payment_type' => $pembayaran?->payment_type,
            'waktu_lunas' => $order->waktu_lunas?->locale('id')->translatedFormat('d M Y, H:i').' WIB',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function baris(Order $order): array
    {
        $pembayaran = $order->payments->sortByDesc('id')->first();

        return [
            'id' => $order->id,
            'date_iso' => $order->created_at?->format('Y-m-d'),
            'datetime' => $this->waktu($order),
            'month' => $order->created_at?->format('Y-m'),
            'month_label' => $order->created_at ? $this->labelBulan($order->created_at->format('Y-m')) : null,
            'code' => $order->kode_pesanan,
            'queue' => $this->nomorAntrean($order),
            'customer' => $order->nama_pelanggan,
            'total' => $order->total,
            'method' => $order->metode_pembayaran->value,
            'method_label' => $order->metode_pembayaran->label(),
            'status' => $order->status_pembayaran->value,
            'status_label' => $order->status_pembayaran->label(),
            'source' => $order->sumber->value,
            'source_label' => $order->sumber->label(),
            'items' => $order->items->map(fn ($item): array => [
                'name' => $item->nama_menu,
                'qty' => $item->jumlah,
                'price' => $item->harga_satuan,
                'subtotal' => $item->subtotal,
            ])->values()->all(),
            'cash_received' => $order->uang_diterima,
            'change' => $order->kembalian,
            'midtrans_id' => $pembayaran?->transaction_id ?? $pembayaran?->midtrans_order_id,
        ];
    }

    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    private function rentang(string $bulan): array
    {
        $mulai = CarbonImmutable::createFromFormat('!Y-m', $bulan, self::ZONA);

        return [$mulai->startOfMonth(), $mulai->endOfMonth()];
    }

    private function labelBulan(string $bulan): string
    {
        return CarbonImmutable::createFromFormat('!Y-m', $bulan, self::ZONA)
            ->locale('id')
            ->translatedFormat('F Y');
    }

    private function waktu(Order $order): string
    {
        return $order->created_at
            ? $order->created_at->locale('id')->translatedFormat('d M Y, H:i').' WIB'
            : '-';
    }

    private function nomorAntrean(Order $order): ?string
    {
        return $order->nomor_antrean === null
            ? null
            : str_pad((string) $order->nomor_antrean, 3, '0', STR_PAD_LEFT);
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\Kasir\StoreOrderRequest;
use App\Http\Requests\Kasir\UpdateMenuAvailabilityRequest;
use App\Http\Requests\Kasir\UpdateOrderStatusRequest;
use App\Http\Resources\MenuResource;
use App\Http\Resources\OrderResource;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * Tampilan kasir (front-end).
 *
 * Catatan tahap pengerjaan:
 * - Menu dan kategori dibaca dari database.
 * - Buat Pesanan menyimpan order tunai lewat POST /kasir/pesanan.
 * - Pesanan Masuk membaca order dari database dan bisa mengubah status.
 * - Kelola Menu membaca menu dari database dan mengubah status ketersediaan.
 */
class KasirController extends Controller
{
    public function buatPesanan()
    {
        return view('kasir.pesanan-baru', [
            'kasir' => $this->kasir(),
            'categories' => $this->categories(),
            'menus' => $this->menus(),
        ]);
    }

    public function pesananMasuk()
    {
        return view('kasir.pesanan-masuk', [
            'kasir' => $this->kasir(),
            'orders' => $this->orders(),
        ]);
    }

    public function kelolaMenu()
    {
        return view('kasir.kelola-menu', [
            'kasir' => $this->kasir(),
            'menus' => $this->menus(),
        ]);
    }

    /**
     * Simpan transaksi tunai kasir.
     *
     * Nominal, harga, dan subtotal dihitung ulang di server. Input harga dari
     * browser diabaikan. Respons berisi nomor antrean dan kembalian untuk
     * ditampilkan pada dialog konfirmasi.
     */
    public function storeOrder(StoreOrderRequest $request, OrderService $orderService): JsonResponse
    {
        $order = $orderService->buatOrderTunai(
            $request->validated(),
            $request->user(),
        );

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Ubah status pesanan: antre → diproses → selesai.
     *
     * Transisi yang tidak sah ditolak `OrderService` dengan respons 422.
     */
    public function updateStatus(
        UpdateOrderStatusRequest $request,
        Order $order,
        OrderService $orderService,
    ): JsonResponse {
        $order = $orderService->ubahStatus($order, $request->statusPesanan());

        return (new OrderResource($order))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Ubah status ketersediaan menu (Tersedia/Habis).
     *
     * Kasir hanya boleh mengubah kolom `tersedia`; data menu lain milik admin.
     */
    public function updateAvailability(
        UpdateMenuAvailabilityRequest $request,
        Menu $menu,
    ): JsonResponse {
        $menu->update(['tersedia' => $request->tersedia()]);

        return (new MenuResource($menu->refresh()))
            ->response()
            ->setStatusCode(200);
    }

    private function kasir(): array
    {
        $user = auth()->user();

        return [
            'name' => $user->name,
            'short_name' => $user->namaDepan(),
            'initial' => $user->inisial(),
            'role' => $user->role->label(),
        ];
    }

    private function categories(): array
    {
        return [
            ['slug' => 'all', 'label' => 'Semua'],
            ...Category::query()
                ->ordered()
                ->get()
                ->map(fn (Category $category): array => [
                    'slug' => $category->slug,
                    'label' => $category->nama,
                ])
                ->all(),
        ];
    }

    private function menus(): array
    {
        return Menu::query()
            ->with('category')
            ->ordered()
            ->get()
            ->map(fn (Menu $menu): array => [
                'id' => $menu->id,
                'slug' => $menu->slug,
                'name' => $menu->nama,
                'category' => $menu->category->slug,
                'category_label' => $menu->category->nama,
                'price' => $menu->harga,
                'description' => $menu->deskripsi,
                'spec' => $menu->category->nama,
                'image' => $menu->foto,
                'featured' => false,
                'available' => $menu->tersedia,
            ])
            ->all();
    }

    /**
     * Daftar pesanan hari ini untuk halaman Pesanan Masuk.
     *
     * Order QRIS yang belum dibayar belum punya nomor antrean, jadi filter
     * memakai tanggal dibuat agar pesanan itu tetap tampil di tab Belum Dibayar.
     */
    private function orders(): array
    {
        return Order::query()
            ->with('items')
            ->whereDate('created_at', now()->toDateString())
            ->latest('created_at')
            ->get()
            ->map(fn (Order $order): array => [
                'id' => (string) $order->id,
                'code' => $order->kode_pesanan,
                'queue' => $order->nomor_antrean === null
                    ? null
                    : str_pad((string) $order->nomor_antrean, 3, '0', STR_PAD_LEFT),
                'customer' => $order->nama_pelanggan,
                'type' => $order->tipe_pesanan->value,
                'type_label' => $order->tipe_pesanan->label(),
                'source' => $order->sumber->value,
                'status' => $order->status_pesanan->value,
                'payment' => $order->metode_pembayaran->value,
                'payment_label' => $order->metode_pembayaran->label(),
                'payment_status' => $order->status_pembayaran->value,
                'total' => $order->total,
                'created_at' => $this->formatWaktu($order->created_at),
                'midtrans_id' => null,
                'cash_received' => $order->uang_diterima,
                'change' => $order->kembalian,
                'expires_at' => $order->batas_waktu_pembayaran
                    ? $this->formatWaktu($order->batas_waktu_pembayaran)
                    : null,
                'notes' => $order->catatan,
                'items' => $order->items
                    ->map(fn ($item): array => [
                        'name' => $item->nama_menu,
                        'price' => $item->harga_satuan,
                        'qty' => $item->jumlah,
                        'subtotal' => $item->subtotal,
                        'note' => $item->catatan,
                    ])
                    ->all(),
            ])
            ->all();
    }

    /**
     * Format waktu tampilan kasir: "23 Okt 2026 • 21:04 WIB".
     */
    private function formatWaktu(?Carbon $waktu): ?string
    {
        if (! $waktu) {
            return null;
        }

        $bulan = [
            1 => 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun',
            'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des',
        ];

        $lokal = $waktu->copy()->timezone('Asia/Jakarta');

        return sprintf(
            '%d %s %d • %s WIB',
            $lokal->day,
            $bulan[$lokal->month],
            $lokal->year,
            $lokal->format('H:i'),
        );
    }
}

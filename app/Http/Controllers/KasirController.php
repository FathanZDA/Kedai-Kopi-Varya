<?php

namespace App\Http\Controllers;

use App\Enums\StatusPesanan;
use App\Http\Requests\Kasir\PollOrdersRequest;
use App\Http\Requests\Kasir\StoreOrderRequest;
use App\Http\Requests\Kasir\UpdateMenuAvailabilityRequest;
use App\Http\Requests\Kasir\UpdateOrderStatusRequest;
use App\Http\Resources\MenuResource;
use App\Http\Resources\OrderResource;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Services\OrderService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

/**
 * Tampilan kasir (front-end).
 *
 * Catatan tahap pengerjaan:
 * - Menu dan kategori dibaca dari database.
 * - Buat Pesanan menyimpan order tunai lewat POST /kasir/pesanan.
 * - Pesanan Masuk membaca order dari database, bisa mengubah status, dan
 *   menyegarkan daftar lewat polling GET /kasir/pesanan/poll.
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
     * Polling Pesanan Masuk.
     *
     * Mengembalikan pesanan hari ini (atau hanya yang berubah sejak `sejak`),
     * jumlah per status, dan `waktu_server` untuk dipakai sebagai `sejak`
     * pada permintaan berikutnya. Kontrak: docs/ROUTES.md §4.5.
     */
    public function pollOrders(PollOrdersRequest $request): JsonResponse
    {
        // Diambil sebelum query: baris yang berubah saat query berjalan tetap
        // terkirim pada polling berikutnya, bukan hilang.
        $waktuServer = now();
        $pesanan = $this->daftarOrderHariIni($request->sejak());

        return response()->json([
            'data' => [
                'waktu_server' => $waktuServer->toIso8601String(),
                'jumlah' => $this->jumlahPesananHariIni(),
                'pesanan' => $pesanan
                    ->map(fn (Order $order): array => $this->payloadPesanan($order))
                    ->all(),
            ],
        ]);
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
        return $this->daftarOrderHariIni()
            ->map(fn (Order $order): array => $this->payloadPesanan($order))
            ->all();
    }

    /**
     * Order yang dibuat hari ini, opsional hanya yang berubah sejak waktu tertentu.
     *
     * Perbandingan memakai `>=` karena kolom timestamp MySQL berpresisi detik;
     * mengirim ulang satu baris lebih aman daripada melewatkan perubahan status
     * yang terjadi pada detik yang sama dengan `sejak`.
     *
     * @return Collection<int, Order>
     */
    private function daftarOrderHariIni(?CarbonInterface $sejak = null): Collection
    {
        return Order::query()
            ->with('items')
            ->whereDate('created_at', now()->toDateString())
            ->when($sejak, fn (Builder $query) => $query->where('updated_at', '>=', $sejak))
            ->latest('created_at')
            ->get();
    }

    /**
     * Jumlah pesanan hari ini per status untuk kontrak polling.
     *
     * @return array<string, int>
     */
    private function jumlahPesananHariIni(): array
    {
        $jumlah = Order::query()
            ->whereDate('created_at', now()->toDateString())
            ->selectRaw('status_pesanan, COUNT(*) as total')
            ->groupBy('status_pesanan')
            ->pluck('total', 'status_pesanan');

        return [
            'belum_dibayar' => (int) ($jumlah[StatusPesanan::MenungguPembayaran->value] ?? 0),
            'antre' => (int) ($jumlah[StatusPesanan::Antre->value] ?? 0),
            'diproses' => (int) ($jumlah[StatusPesanan::Diproses->value] ?? 0),
            'selesai' => (int) ($jumlah[StatusPesanan::Selesai->value] ?? 0),
        ];
    }

    /**
     * Bentuk data satu pesanan untuk halaman Pesanan Masuk dan polling.
     *
     * @return array<string, mixed>
     */
    private function payloadPesanan(Order $order): array
    {
        return [
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
        ];
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

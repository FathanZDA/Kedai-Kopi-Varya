<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\AboutPhotoRequest;
use App\Http\Requests\Admin\MenuRequest;
use App\Http\Requests\Admin\PromoRequest;
use App\Http\Requests\Admin\StoreSettingsRequest;
use App\Http\Requests\Admin\UpdateMenuAvailabilityRequest;
use App\Http\Requests\Admin\UpdatePromoStatusRequest;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Promo;
use App\Models\StoreHour;
use App\Models\StoreSetting;
use App\Services\DashboardService;
use App\Services\MenuCatalogService;
use App\Services\PromoService;
use App\Services\ReportService;
use App\Services\StoreSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Tampilan admin / owner (front-end).
 *
 * Catatan tahap pengerjaan:
 * - Menu, kategori, promosi, dan informasi toko dibaca dari database.
 * - Tambah, ubah, hapus (soft delete), dan ketersediaan menu sudah tersimpan
 *   ke database lewat `MenuCatalogService`; foto diunggah ke
 *   `public/images/menus`.
 * - Promosi juga sudah tersimpan ke database (`PromoService`), termasuk
 *   status aktif dan unggah gambar ke `public/images/promos`.
 * - Informasi toko (profil, jam operasional, alamat, kontak, fasilitas) sudah
 *   tersimpan lewat `StoreSettingsService` (`PUT /admin/toko`).
 * - Laporan transaksi sudah membaca `orders`, `order_items`, dan `payments`
 *   lewat `ReportService`.
 * - Dashboard juga sudah membaca database lewat `DashboardService`; tidak ada
 *   lagi data transaksi contoh di controller.
 */
class AdminController extends Controller
{
    public function dashboard(DashboardService $dashboard): View
    {
        $data = $dashboard->data();
        $menus = $this->menuData();

        return view('admin.dashboard', [
            'admin' => $this->admin(),
            'orders' => $data['recent_orders'],
            'menus' => $menus,
            'popularMenus' => $data['popular_menus'],
            'summary' => [
                'revenue' => $data['month_revenue'],
                'count' => $data['month_count'],
                'qris_percent' => $data['qris_percent'],
                'cash_percent' => $data['cash_percent'],
            ],
            'pendingPayments' => $data['pending_payments'],
            'processingCount' => $data['processing'],
            'todayRevenue' => $data['today_revenue'],
            'todayOrders' => $data['today_orders'],
            'yesterdayOrders' => $data['yesterday_orders'],
            'ordersChangePercent' => $data['orders_change_percent'],
            'todayLabel' => $data['today_label'],
            'monthLabel' => $data['month_label'],
            'config' => [
                'orders' => $data['recent_orders'],
                'menus' => $menus,
            ],
        ]);
    }

    public function menus()
    {
        $admin = $this->admin();
        $menus = $this->menuData();

        return view('admin.menu.index', [
            'admin' => $admin,
            'menus' => $menus,
            'categories' => $this->categories(),
            'config' => [
                'menus' => $menus,
                'categories' => $this->categories(),
            ],
        ]);
    }

    public function createMenu()
    {
        $admin = $this->admin();
        $menus = $this->menuData();

        return view('admin.menu.form', [
            'admin' => $admin,
            'menu' => null,
            'categories' => $this->categories(),
            'config' => [
                'menus' => $menus,
                'menu' => null,
                'categories' => $this->categories(),
            ],
        ]);
    }

    public function storeMenu(MenuRequest $request, MenuCatalogService $katalog): RedirectResponse
    {
        $menu = $katalog->buat($request->dataMenu());

        return redirect()
            ->route('admin.menu.index')
            ->with('status', "Menu \"{$menu->nama}\" berhasil ditambahkan.");
    }

    public function editMenu(Menu $menu)
    {
        $admin = $this->admin();
        $menus = $this->menuData();
        $selected = collect($menus)->firstWhere('id', $menu->id);

        abort_if($selected === null, 404);

        return view('admin.menu.form', [
            'admin' => $admin,
            'menu' => $selected,
            'categories' => $this->categories(),
            'config' => [
                'menus' => $menus,
                'menu' => $selected,
                'categories' => $this->categories(),
            ],
        ]);
    }

    public function updateMenu(MenuRequest $request, Menu $menu, MenuCatalogService $katalog): RedirectResponse
    {
        $katalog->ubah($menu, $request->dataMenu());

        return redirect()
            ->route('admin.menu.index')
            ->with('status', "Menu \"{$menu->nama}\" berhasil diperbarui.");
    }

    public function destroyMenu(Menu $menu, MenuCatalogService $katalog): RedirectResponse
    {
        $nama = $menu->nama;
        $katalog->hapus($menu);

        return redirect()
            ->route('admin.menu.index')
            ->with('status', "Menu \"{$nama}\" berhasil dihapus.");
    }

    public function updateMenuAvailability(
        UpdateMenuAvailabilityRequest $request,
        Menu $menu,
        MenuCatalogService $katalog,
    ): JsonResponse {
        $katalog->aturKetersediaan($menu, $request->tersedia());

        return response()->json([
            'message' => "Menu \"{$menu->nama}\" ditandai ".($menu->tersedia ? 'tersedia' : 'habis').'.',
            'data' => [
                'id' => $menu->id,
                'tersedia' => $menu->tersedia,
            ],
        ]);
    }

    public function reports(Request $request, ReportService $laporan)
    {
        $bulan = $laporan->bulanValid($request->query('bulan'));
        $transaksi = $laporan->transaksi($bulan);
        $bulanList = $laporan->daftarBulan($bulan);

        return view('admin.laporan', [
            'admin' => $this->admin(),
            'transactions' => $transaksi,
            'months' => $bulanList,
            'defaultMonth' => $bulan,
            'summary' => $laporan->ringkasan($bulan),
            'config' => [
                'transactions' => $transaksi,
                'months' => $bulanList,
                'defaultMonth' => $bulan,
            ],
        ]);
    }

    /**
     * Rincian satu transaksi untuk dialog laporan.
     */
    public function showReportOrder(Order $order, ReportService $laporan): JsonResponse
    {
        return response()->json(['data' => $laporan->detail($order)]);
    }

    public function promos()
    {
        $admin = $this->admin();
        $promos = $this->promoData();
        $store = $this->storeData();

        return view('admin.promosi', [
            'admin' => $admin,
            'promos' => $promos,
            'store' => $store,
            'config' => [
                'promos' => $promos,
                'aboutImage' => $store['about_image'] ?? asset('images/toko/belum-ada-foto.svg'),
            ],
        ]);
    }

    public function storePromo(PromoRequest $request, PromoService $promoService): RedirectResponse
    {
        $promo = $promoService->buat($request->dataPromo());

        return redirect()
            ->route('admin.promos')
            ->with('status', "Promosi \"{$promo->judul}\" berhasil ditambahkan.");
    }

    public function updatePromo(PromoRequest $request, Promo $promo, PromoService $promoService): RedirectResponse
    {
        $promoService->ubah($promo, $request->dataPromo());

        return redirect()
            ->route('admin.promos')
            ->with('status', "Promosi \"{$promo->judul}\" berhasil diperbarui.");
    }

    public function destroyPromo(
        Request $request,
        Promo $promo,
        PromoService $promoService,
    ): RedirectResponse|JsonResponse {
        $judul = $promo->judul;
        $id = $promo->id;

        $promoService->hapus($promo);

        $pesan = "Promosi \"{$judul}\" berhasil dihapus.";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $pesan,
                'data' => ['id' => $id],
            ]);
        }

        return redirect()->route('admin.promos')->with('status', $pesan);
    }

    public function updatePromoStatus(
        UpdatePromoStatusRequest $request,
        Promo $promo,
        PromoService $promoService,
    ): JsonResponse {
        $promoService->aturStatus($promo, $request->aktif());

        return response()->json([
            'message' => "Promosi \"{$promo->judul}\" ".($promo->aktif ? 'diaktifkan' : 'dinonaktifkan').'.',
            'data' => [
                'id' => $promo->id,
                'aktif' => $promo->aktif,
            ],
        ]);
    }

    public function store()
    {
        $admin = $this->admin();
        $store = $this->storeData();

        return view('admin.toko', [
            'admin' => $admin,
            'store' => $store,
            'config' => [
                'store' => $store,
            ],
        ]);
    }

    public function updateStore(StoreSettingsRequest $request, StoreSettingsService $settings): RedirectResponse
    {
        $settings->simpan($request->dataToko());

        return redirect()
            ->route('admin.store')
            ->with('status', 'Informasi toko berhasil disimpan.');
    }

    /**
     * Unggah foto halaman Tentang Kami.
     *
     * Form memakai `POST` multipart dari panel di halaman promosi, terpisah
     * dari `PUT /admin/toko` yang JSON, agar kontrak Informasi Toko tidak
     * berubah.
     */
    public function storeAboutPhoto(
        AboutPhotoRequest $request,
        StoreSettingsService $settings,
    ): RedirectResponse {
        $settings->simpanFotoTentang($request->file('foto'));

        return redirect()
            ->route('admin.promos')
            ->with('status', 'Foto tentang kami berhasil disimpan.');
    }

    /**
     * Hapus foto halaman Tentang Kami.
     */
    public function destroyAboutPhoto(
        Request $request,
        StoreSettingsService $settings,
    ): RedirectResponse|JsonResponse {
        $settings->hapusFotoTentang();

        $pesan = 'Foto tentang kami berhasil dihapus.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $pesan,
                'data' => ['about_image' => null],
            ]);
        }

        return redirect()->route('admin.promos')->with('status', $pesan);
    }

    private function admin(): array
    {
        $user = auth()->user();

        return [
            'name' => $user->name,
            'initial' => $user->inisial(),
            'role' => $user->role->label(),
        ];
    }

    private function categories(): array
    {
        return Category::query()
            ->ordered()
            ->get()
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'slug' => $category->slug,
                'label' => $category->nama,
            ])
            ->all();
    }

    private function menuData(): array
    {
        $skuPrefixes = [
            'kopi' => 'KOP',
            'teh' => 'TEH',
            'autentik' => 'AUT',
            'makanan' => 'MKN',
            'cemilan' => 'CML',
        ];

        $skuCounters = [];

        return Menu::query()
            ->with('category')
            ->ordered()
            ->get()
            ->map(function (Menu $menu) use (&$skuCounters, $skuPrefixes): array {
                $category = $menu->category->slug;
                $skuCounters[$category] = ($skuCounters[$category] ?? 0) + 1;

                return [
                    'id' => $menu->id,
                    'name' => $menu->nama,
                    'sku' => ($skuPrefixes[$category] ?? 'SKU').'-'.str_pad((string) $skuCounters[$category], 3, '0', STR_PAD_LEFT),
                    'category_id' => $menu->category_id,
                    'category' => $category,
                    'category_label' => $menu->category->nama,
                    'price' => $menu->harga,
                    'description' => $menu->deskripsi,
                    'image' => $menu->foto ? asset($menu->foto) : asset('images/menus/varya-signature.jpg'),
                    'available' => $menu->tersedia,
                    'updated_at' => $menu->updated_at?->locale('id')->translatedFormat('j F Y, H:i').' WIB',
                    'deleted' => false,
                ];
            })
            ->all();
    }

    private function promoData(): array
    {
        return Promo::query()
            ->ordered()
            ->get()
            ->map(fn (Promo $promo): array => [
                'id' => $promo->id,
                'title' => $promo->judul,
                'description' => $promo->deskripsi,
                'image' => $promo->gambar ? asset($promo->gambar) : asset('images/menus/varya-signature.jpg'),
                'order' => $promo->urutan,
                'period_start' => $promo->mulai?->toDateString(),
                'period_end' => $promo->berakhir?->toDateString(),
                'active' => $promo->aktif,
            ])
            ->all();
    }

    private function storeData(): array
    {
        $store = StoreSetting::instance();

        $hours = StoreHour::query()
            ->ordered()
            ->get()
            ->map(fn (StoreHour $hour): array => [
                'hari' => $hour->hari,
                'key' => $this->hariKey($hour->hari),
                'day' => $hour->hari_label,
                'open' => substr((string) $hour->jam_buka, 0, 5),
                'close' => substr((string) $hour->jam_tutup, 0, 5),
                'closed' => $hour->tutup || ! $hour->jam_buka || ! $hour->jam_tutup,
            ])
            ->all();

        return [
            'name' => $store->nama_toko ?? 'Kedai Kopi Varya',
            'tagline' => $store->tagline ?? '',
            'description' => $store->sejarah ?? '',
            'address' => $store->alamat ?? '',
            'maps_url' => $store->maps_url ?? '',
            'maps_embed' => $store->maps_embed ?? '',
            'instagram' => $store->instagram ?? '',
            'phone' => $store->telepon ?? '',
            'whatsapp' => $store->whatsapp ?? '',
            'facilities' => $store->fasilitas ?? [],
            'points' => [
                ['value' => '100%', 'label' => 'Kopi Lokal'],
                ['value' => '15+', 'label' => 'Kreasi Kopi & Non-Kopi'],
                ['value' => 'Setiap Hari', 'label' => 'Siap Melayani'],
            ],
            'hours' => $hours,
            'about_image' => $store->foto_tentang ? asset($store->foto_tentang) : null,
            'last_updated' => $store->updated_at?->locale('id')->translatedFormat('j F Y, H:i').' WIB' ?? 'Belum diperbarui',
        ];
    }

    private function hariKey(int $hari): string
    {
        return match ($hari) {
            0 => 'minggu',
            1 => 'senin',
            2 => 'selasa',
            3 => 'rabu',
            4 => 'kamis',
            5 => 'jumat',
            6 => 'sabtu',
            default => 'senin',
        };
    }
}

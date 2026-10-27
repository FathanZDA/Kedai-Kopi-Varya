<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Promo;
use App\Models\StoreHour;
use App\Models\StoreSetting;
use Carbon\CarbonImmutable;

/**
 * Tampilan admin / owner (front-end).
 *
 * Catatan tahap pengerjaan:
 * - Menu, kategori, promosi, dan informasi toko dibaca dari database.
 * - Pesanan dan laporan masih data demo sampai slice backend tersedia.
 * - Belum ada autentikasi, upload permanen, atau logika backend transaksi.
 * - Perubahan menu, promosi, dan informasi toko masih disimpan sementara di
 *   browser melalui resources/js/admin.js agar alur demo terasa utuh.
 */
class AdminController extends Controller
{
    public function dashboard()
    {
        $admin = $this->admin();
        $kodeTunai = $this->kodePesanan('demo-order-20261016-2JC9');
        $orders = array_map(static function (array $order) use ($kodeTunai): array {
            $payments = [$kodeTunai => 'Tunai'];
            $order['payment'] = $payments[$order['code']] ?? 'QRIS';

            return $order;
        }, $this->recentOrders());
        $menus = $this->menuData();
        $transactions = $this->transactions();
        $currentMonth = '2026-10';
        $summary = $this->summarizeMonth($transactions, $currentMonth);
        $pendingPayments = collect($transactions)
            ->where('month', $currentMonth)
            ->whereIn('status', ['menunggu', 'perlu_diperiksa'])
            ->count();
        $todayRevenue = collect($transactions)
            ->where('date_iso', '2026-10-16')
            ->where('status', 'lunas')
            ->sum('total');
        $demoDate = CarbonImmutable::parse('2026-10-16 21:42', 'Asia/Jakarta');

        return view('admin.dashboard', [
            'admin' => $admin,
            'orders' => $orders,
            'menus' => $menus,
            'popularMenus' => $this->popularMenus(),
            'summary' => $summary,
            'pendingPayments' => $pendingPayments,
            'todayRevenue' => $todayRevenue,
            'yesterdayOrders' => 7,
            'todayLabel' => $demoDate->locale('id')->translatedFormat('l, j F Y').' // '.$demoDate->format('H:i').' WIB',
            'config' => [
                'orders' => $orders,
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

    public function editMenu(string $menu)
    {
        $admin = $this->admin();
        $menus = $this->menuData();
        $selected = collect($menus)->firstWhere('id', (int) $menu);

        // Menu baru dari localStorage belum ada di data contoh controller.
        // Halaman tetap dirender; admin.js akan mengisi datanya dari browser.
        $selected ??= [
            'id' => (int) $menu,
            'name' => 'Menu Baru',
            'sku' => 'SKU-BARU',
            'category' => 'kopi',
            'category_label' => 'Kopi',
            'price' => 0,
            'description' => '',
            'image' => asset('images/menus/varya-signature.jpg'),
            'available' => true,
        ];

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

    public function reports()
    {
        $admin = $this->admin();
        $transactions = $this->transactions();
        $defaultMonth = '2026-10';
        $months = collect($transactions)
            ->map(fn (array $transaction): array => [
                'value' => $transaction['month'],
                'label' => $transaction['month_label'],
            ])
            ->unique('value')
            ->sortByDesc('value')
            ->values()
            ->all();

        return view('admin.laporan', [
            'admin' => $admin,
            'transactions' => $transactions,
            'months' => $months,
            'defaultMonth' => $defaultMonth,
            'summary' => $this->summarizeMonth($transactions, $defaultMonth),
            'config' => [
                'transactions' => $transactions,
                'months' => $months,
                'defaultMonth' => $defaultMonth,
            ],
        ]);
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
                'aboutImage' => $store['about_image'],
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
                    'category' => $category,
                    'category_label' => $menu->category->nama,
                    'price' => $menu->harga,
                    'description' => $menu->deskripsi,
                    'image' => $menu->foto ? asset($menu->foto) : asset('images/menus/varya-signature.jpg'),
                    'available' => $menu->tersedia,
                    'deleted' => false,
                ];
            })
            ->all();
    }

    private function recentOrders(): array
    {
        $orders = [
            [
                'code' => 'demo-order-20261016-2JC9',
                'customer' => 'P (Pelanggan)',
                'type' => 'dine_in',
                'type_label' => 'Di Tempat',
                'items' => 1,
                'portions' => 3,
                'total' => 84000,
                'status' => 'selesai',
                'status_label' => 'Selesai',
                'time' => '8 menit lalu',
            ],
            [
                'code' => 'demo-order-20261016-1MEY',
                'customer' => 'Fathan Zada',
                'type' => 'dine_in',
                'type_label' => 'Di Tempat',
                'items' => 3,
                'portions' => 3,
                'total' => 105000,
                'status' => 'diproses',
                'status_label' => 'Diproses',
                'time' => '18 menit lalu',
            ],
            [
                'code' => 'demo-order-20261016-4EFV',
                'customer' => 'Marcell',
                'type' => 'dine_in',
                'type_label' => 'Di Tempat',
                'items' => 1,
                'portions' => 1,
                'total' => 24000,
                'status' => 'selesai',
                'status_label' => 'Selesai',
                'time' => '42 menit lalu',
            ],
            [
                'code' => 'demo-order-20261004-5UR7',
                'customer' => 'Steven Pratama',
                'type' => 'take_away',
                'type_label' => 'Bawa Pulang',
                'items' => 2,
                'portions' => 10,
                'total' => 296000,
                'status' => 'selesai',
                'status_label' => 'Selesai',
                'time' => '1 jam lalu',
            ],
            [
                'code' => 'demo-order-20261016-7KQ2',
                'customer' => 'Anjani',
                'type' => 'take_away',
                'type_label' => 'Bawa Pulang',
                'items' => 2,
                'portions' => 2,
                'total' => 56000,
                'status' => 'diproses',
                'status_label' => 'Diproses',
                'time' => '3 menit lalu',
            ],
            [
                'code' => 'demo-order-20261016-9ZP4',
                'customer' => 'Rian Pratama',
                'type' => 'dine_in',
                'type_label' => 'Di Tempat',
                'items' => 4,
                'portions' => 4,
                'total' => 132000,
                'status' => 'selesai',
                'status_label' => 'Selesai',
                'time' => '6 menit lalu',
            ],
            [
                'code' => 'demo-order-20261016-3HXN',
                'customer' => 'Salsabila Putri',
                'type' => 'take_away',
                'type_label' => 'Bawa Pulang',
                'items' => 1,
                'portions' => 1,
                'total' => 32000,
                'status' => 'diproses',
                'status_label' => 'Diproses',
                'time' => '11 menit lalu',
            ],
            [
                'code' => 'demo-order-20261016-6BVM',
                'customer' => 'Bagas',
                'type' => 'dine_in',
                'type_label' => 'Di Tempat',
                'items' => 2,
                'portions' => 2,
                'total' => 48000,
                'status' => 'selesai',
                'status_label' => 'Selesai',
                'time' => '25 menit lalu',
            ],
            [
                'code' => 'demo-order-20261015-8QWE',
                'customer' => 'Nadia Kirana',
                'type' => 'take_away',
                'type_label' => 'Bawa Pulang',
                'items' => 1,
                'portions' => 1,
                'total' => 22000,
                'status' => 'selesai',
                'status_label' => 'Selesai',
                'time' => '1 hari lalu',
            ],
            [
                'code' => 'demo-order-20261015-2PLM',
                'customer' => 'Fajar Ramadhan',
                'type' => 'dine_in',
                'type_label' => 'Di Tempat',
                'items' => 3,
                'portions' => 3,
                'total' => 88000,
                'status' => 'selesai',
                'status_label' => 'Selesai',
                'time' => '2 hari lalu',
            ],
        ];

        return array_map(fn (array $order): array => [
            ...$order,
            'code' => $this->kodePesanan($order['code']),
        ], $orders);
    }

    private function popularMenus(): array
    {
        return [
            [
                'name' => 'Varya Signature',
                'price' => 22000,
                'note' => 'Aren & Fresh Milk',
                'sales' => '14x terjual',
                'tag' => 'Kopi Terlaris',
                'image' => asset('images/menus/varya-signature.jpg'),
            ],
            [
                'name' => 'Caramel Macchiato',
                'price' => 25000,
                'note' => 'Karamel Gurih',
                'sales' => '11x terjual',
                'tag' => 'Favorit Sore',
                'image' => asset('images/menus/caramel-macchiato.jpg'),
            ],
            [
                'name' => 'Matcha Creamy',
                'price' => 24000,
                'note' => 'Murni Uji',
                'sales' => '9x terjual',
                'tag' => 'Teh Terlaris',
                'image' => asset('images/menus/matcha-creamy.jpg'),
            ],
            [
                'name' => 'Almond Croissant',
                'price' => 26000,
                'note' => 'Baru Dipanggang',
                'sales' => '8x terjual',
                'tag' => 'Pasangan Kopi',
                'image' => asset('images/menus/almond-croissant.jpg'),
            ],
            [
                'name' => 'Roti Bakar Mix',
                'price' => 20000,
                'note' => 'Cokelat & Keju',
                'sales' => '6x terjual',
                'tag' => 'Cemilan Favorit',
                'image' => asset('images/menus/roti-bakar-mix.jpg'),
            ],
        ];
    }

    private function transactions(): array
    {
        $definitions = [
            // Oktober 2026 - total transaksi lunas: Rp 647.000.
            [
                'date' => '2026-10-16 03:37',
                'code' => 'demo-transaksi-20261016-DPV3',
                'order' => 'demo-order-20261016-2JC9',
                'customer' => 'P (0979766)',
                'method' => 'tunai',
                'status' => 'lunas',
                'source' => 'kasir',
                'items' => [
                    ['Varya Signature', 1, 20000],
                    ['Butter French Croissant', 1, 24000],
                    ['Es Kopi Susu Gula Aren', 2, 20000],
                ],
                'cash_received' => 100000,
            ],
            [
                'date' => '2026-10-16 03:37',
                'code' => 'demo-transaksi-20261006-UHCN',
                'order' => 'demo-order-20261006-1MEY',
                'customer' => 'Fathan Zada',
                'method' => 'qris',
                'status' => 'lunas',
                'source' => 'qr',
                'items' => [
                    ['Artisan Cappuccino', 2, 28000],
                    ['Caramel Macchiato', 1, 25000],
                    ['Butter French Croissant', 1, 24000],
                ],
            ],
            [
                'date' => '2026-10-06 12:41',
                'code' => 'demo-transaksi-20261006-4EFV',
                'order' => 'demo-order-20261006-SXY1',
                'customer' => 'Marcell',
                'method' => 'qris',
                'status' => 'lunas',
                'source' => 'qr',
                'items' => [
                    ['Matcha Creamy', 1, 24000],
                ],
            ],
            [
                'date' => '2026-10-04 06:05',
                'code' => 'demo-transaksi-20261004-FMJE',
                'order' => 'demo-order-20261004-5UR7',
                'customer' => 'Steven Pratama',
                'method' => 'qris',
                'status' => 'lunas',
                'source' => 'qr',
                'items' => [
                    ['Varya Signature', 4, 22000],
                    ['Basque Burnt Cheesecake', 2, 35000],
                    ['Truffle Fries', 3, 28000],
                    ['Artisan Jasmine Tea', 2, 18000],
                    ['Mie Gacoan Varya Special', 1, 18000],
                ],
            ],
            [
                'date' => '2026-10-01 01:42',
                'code' => 'demo-transaksi-20261001-DPYI',
                'order' => 'demo-order-20261001-ACZP',
                'customer' => 'Than',
                'method' => 'tunai',
                'status' => 'lunas',
                'source' => 'kasir',
                'items' => [
                    ['Truffle Fries', 1, 28000],
                ],
                'cash_received' => 50000,
            ],
            [
                'date' => '2026-10-01 12:07',
                'code' => 'demo-transaksi-20261001-001',
                'order' => 'demo-order-20261001-001',
                'customer' => 'Fathan',
                'method' => 'qris',
                'status' => 'lunas',
                'source' => 'qr',
                'items' => [
                    ['Artisan Cappuccino', 2, 28000],
                ],
            ],
            [
                'date' => '2026-10-02 14:00',
                'code' => 'demo-transaksi-20261002-002',
                'order' => 'demo-order-20261002-002',
                'customer' => 'Dewi',
                'method' => 'qris',
                'status' => 'lunas',
                'source' => 'qr',
                'items' => [
                    ['Ceremonial Matcha Latte', 1, 30000],
                    ['Butter French Croissant', 1, 24000],
                ],
            ],
            [
                'date' => '2026-09-29 19:24',
                'code' => 'demo-transaksi-20260929-002',
                'order' => 'demo-order-20260929-002',
                'customer' => 'Rani',
                'method' => 'tunai',
                'status' => 'lunas',
                'source' => 'kasir',
                'items' => [
                    ['Varya Signature', 2, 22000],
                    ['Basque Burnt Cheesecake', 1, 35000],
                    ['Artisan Jasmine Tea', 1, 18000],
                ],
                'cash_received' => 120000,
            ],
            [
                'date' => '2026-10-16 04:12',
                'code' => 'demo-transaksi-20261016-PEND',
                'order' => 'demo-order-20261016-PEND',
                'customer' => 'Nadia Kirana',
                'method' => 'qris',
                'status' => 'menunggu',
                'source' => 'qr',
                'items' => [
                    ['Varya Signature', 2, 22000],
                    ['Truffle Fries', 1, 22000],
                ],
            ],
            [
                'date' => '2026-10-16 04:18',
                'code' => 'demo-transaksi-20261016-PERIK',
                'order' => 'demo-order-20261016-PERIK',
                'customer' => 'Fajar Ramadhan',
                'method' => 'qris',
                'status' => 'perlu_diperiksa',
                'source' => 'qr',
                'items' => [
                    ['Varya Signature', 1, 22000],
                    ['Ceremonial Matcha Latte', 1, 30000],
                ],
            ],
            [
                'date' => '2026-10-15 21:18',
                'code' => 'demo-transaksi-20261015-GAGAL',
                'order' => 'demo-order-20261015-GAGAL',
                'customer' => 'Dimas',
                'method' => 'qris',
                'status' => 'gagal',
                'source' => 'qr',
                'items' => [
                    ['Salted Caramel Macchiato', 1, 32000],
                ],
            ],
            // September 2026.
            [
                'date' => '2026-09-28 18:40',
                'code' => 'demo-transaksi-20260928-003',
                'order' => 'demo-order-20260928-003',
                'customer' => 'Alya',
                'method' => 'qris',
                'status' => 'lunas',
                'source' => 'qr',
                'items' => [
                    ['Varya Signature', 1, 22000],
                    ['Truffle Fries', 1, 28000],
                    ['Artisan Jasmine Tea', 1, 18000],
                ],
            ],
            [
                'date' => '2026-09-25 20:11',
                'code' => 'demo-transaksi-20260925-004',
                'order' => 'demo-order-20260925-004',
                'customer' => 'Bagas',
                'method' => 'qris',
                'status' => 'lunas',
                'source' => 'qr',
                'items' => [
                    ['Varya Signature', 2, 22000],
                    ['Truffle Fries', 1, 28000],
                    ['Artisan Jasmine Tea', 1, 18000],
                ],
            ],
            [
                'date' => '2026-09-22 17:08',
                'code' => 'demo-transaksi-20260922-005',
                'order' => 'demo-order-20260922-005',
                'customer' => 'Sinta',
                'method' => 'tunai',
                'status' => 'lunas',
                'source' => 'kasir',
                'items' => [
                    ['Varya Signature', 2, 22000],
                ],
                'cash_received' => 50000,
            ],
            [
                'date' => '2026-09-20 21:32',
                'code' => 'demo-transaksi-20260920-006',
                'order' => 'demo-order-20260920-006',
                'customer' => 'Yoga',
                'method' => 'qris',
                'status' => 'lunas',
                'source' => 'qr',
                'items' => [
                    ['Artisan Cappuccino', 2, 28000],
                    ['Caramel Macchiato', 1, 25000],
                ],
            ],
            [
                'date' => '2026-09-18 19:54',
                'code' => 'demo-transaksi-20260918-007',
                'order' => 'demo-order-20260918-007',
                'customer' => 'Maya',
                'method' => 'qris',
                'status' => 'lunas',
                'source' => 'qr',
                'items' => [
                    ['Basque Burnt Cheesecake', 2, 35000],
                    ['Butter French Croissant', 2, 24000],
                ],
            ],
            [
                'date' => '2026-09-15 16:20',
                'code' => 'demo-transaksi-20260915-008',
                'order' => 'demo-order-20260915-008',
                'customer' => 'Raka',
                'method' => 'tunai',
                'status' => 'lunas',
                'source' => 'kasir',
                'items' => [
                    ['Varya Signature', 1, 22000],
                    ['Truffle Fries', 1, 28000],
                    ['Artisan Jasmine Tea', 1, 18000],
                ],
                'cash_received' => 100000,
            ],
            [
                'date' => '2026-09-12 22:03',
                'code' => 'demo-transaksi-20260912-009',
                'order' => 'demo-order-20260912-009',
                'customer' => 'Nabila',
                'method' => 'qris',
                'status' => 'menunggu',
                'source' => 'qr',
                'items' => [
                    ['Matcha Creamy', 2, 24000],
                ],
            ],
            // Agustus 2026.
            [
                'date' => '2026-08-28 20:15',
                'code' => 'demo-transaksi-20260828-010',
                'order' => 'demo-order-20260828-010',
                'customer' => 'Dimas',
                'method' => 'qris',
                'status' => 'lunas',
                'source' => 'qr',
                'items' => [
                    ['Varya Signature', 3, 22000],
                    ['Basque Burnt Cheesecake', 2, 35000],
                    ['Truffle Fries', 1, 28000],
                ],
            ],
            [
                'date' => '2026-08-25 18:42',
                'code' => 'demo-transaksi-20260825-011',
                'order' => 'demo-order-20260825-011',
                'customer' => 'Intan',
                'method' => 'tunai',
                'status' => 'lunas',
                'source' => 'kasir',
                'items' => [
                    ['Artisan Cappuccino', 2, 28000],
                    ['Butter French Croissant', 1, 24000],
                ],
                'cash_received' => 100000,
            ],
            [
                'date' => '2026-08-22 21:09',
                'code' => 'demo-transaksi-20260822-012',
                'order' => 'demo-order-20260822-012',
                'customer' => 'Rizal',
                'method' => 'qris',
                'status' => 'lunas',
                'source' => 'qr',
                'items' => [
                    ['Varya Signature', 4, 22000],
                    ['Dimsum Mentai', 3, 24000],
                    ['Truffle Fries', 2, 28000],
                ],
            ],
            [
                'date' => '2026-08-18 16:34',
                'code' => 'demo-transaksi-20260818-013',
                'order' => 'demo-order-20260818-013',
                'customer' => 'Vina',
                'method' => 'qris',
                'status' => 'lunas',
                'source' => 'qr',
                'items' => [
                    ['Ceremonial Matcha Latte', 1, 30000],
                    ['Basque Burnt Cheesecake', 1, 35000],
                ],
            ],
            [
                'date' => '2026-08-14 19:57',
                'code' => 'demo-transaksi-20260814-014',
                'order' => 'demo-order-20260814-014',
                'customer' => 'Arif',
                'method' => 'tunai',
                'status' => 'lunas',
                'source' => 'kasir',
                'items' => [
                    ['Varya Signature', 2, 22000],
                    ['Roti Bakar Mix', 1, 20000],
                    ['Artisan Jasmine Tea', 1, 18000],
                ],
                'cash_received' => 100000,
            ],
            [
                'date' => '2026-08-10 20:26',
                'code' => 'demo-transaksi-20260810-015',
                'order' => 'demo-order-20260810-015',
                'customer' => 'Laras',
                'method' => 'qris',
                'status' => 'lunas',
                'source' => 'qr',
                'items' => [
                    ['Artisan Cappuccino', 2, 28000],
                    ['Truffle Fries', 1, 28000],
                    ['Matcha Creamy', 1, 24000],
                ],
            ],
            [
                'date' => '2026-08-06 17:12',
                'code' => 'demo-transaksi-20260806-016',
                'order' => 'demo-order-20260806-016',
                'customer' => 'Hendra',
                'method' => 'qris',
                'status' => 'lunas',
                'source' => 'qr',
                'items' => [
                    ['Salted Caramel Macchiato', 2, 32000],
                    ['Butter French Croissant', 1, 24000],
                ],
            ],
            [
                'date' => '2026-08-02 22:45',
                'code' => 'demo-transaksi-20260802-017',
                'order' => 'demo-order-20260802-017',
                'customer' => 'Sari',
                'method' => 'tunai',
                'status' => 'lunas',
                'source' => 'kasir',
                'items' => [
                    ['Varya Signature', 2, 22000],
                    ['Basque Burnt Cheesecake', 1, 35000],
                    ['Truffle Fries', 1, 28000],
                ],
                'cash_received' => 120000,
            ],
        ];

        return array_map(fn (array $definition): array => $this->makeTransaction($definition), $definitions);
    }

    private function makeTransaction(array $definition): array
    {
        $date = CarbonImmutable::parse($definition['date'], 'Asia/Jakarta');
        $items = array_map(function (array $item): array {
            [$nama, $harga] = $this->menuKanonik($item[0], $item[2]);

            return [
                'name' => $nama,
                'qty' => $item[1],
                'price' => $harga,
                'subtotal' => $item[1] * $harga,
            ];
        }, $definition['items']);

        $total = array_sum(array_column($items, 'subtotal'));
        $method = $definition['method'];
        $status = $definition['status'];
        $cashReceived = $definition['cash_received'] ?? null;

        $statusLabels = [
            'lunas' => 'Lunas',
            'menunggu' => 'Menunggu',
            'perlu_diperiksa' => 'Perlu Diperiksa',
            'gagal' => 'Gagal',
            'kedaluwarsa' => 'Kedaluwarsa',
            'dibatalkan' => 'Dibatalkan',
        ];

        $hash = md5($definition['code']);
        $code = $this->kodePesanan($definition['code']);
        $orderCode = $this->kodePesanan($definition['order']);

        return [
            'id' => $code,
            'date_iso' => $date->format('Y-m-d'),
            'datetime' => $date->locale('id')->translatedFormat('d M Y, H:i').' WIB',
            'month' => $date->format('Y-m'),
            'month_label' => $date->locale('id')->translatedFormat('F Y'),
            'code' => $code,
            'order_code' => $orderCode,
            'customer' => $definition['customer'],
            'total' => $total,
            'method' => $method,
            'method_label' => $method === 'qris' ? 'QRIS' : 'Tunai',
            'status' => $status,
            'status_label' => $statusLabels[$status] ?? ucfirst($status),
            'source' => $definition['source'],
            'source_label' => $definition['source'] === 'kasir' ? 'Kasir' : 'QR Meja',
            'items' => $items,
            'cash_received' => $method === 'tunai' ? $cashReceived : null,
            'change' => $method === 'tunai' && $cashReceived !== null ? max(0, $cashReceived - $total) : null,
            'midtrans_id' => $method === 'qris'
                ? substr($hash, 0, 8).'-'.substr($hash, 8, 4).'-'.substr($hash, 12, 4).'-'.substr($hash, 16, 4).'-'.substr($hash, 20, 12)
                : null,
        ];
    }

    private function summarizeMonth(array $transactions, string $month): array
    {
        $paid = array_values(array_filter(
            $transactions,
            static fn (array $transaction): bool => $transaction['month'] === $month && $transaction['status'] === 'lunas'
        ));

        $revenue = array_sum(array_column($paid, 'total'));
        $qris = array_sum(array_map(
            static fn (array $transaction): int => $transaction['method'] === 'qris' ? $transaction['total'] : 0,
            $paid
        ));
        $cash = $revenue - $qris;
        $count = count($paid);

        return [
            'revenue' => $revenue,
            'count' => $count,
            'qris' => $qris,
            'cash' => $cash,
            'average' => $count > 0 ? (int) round($revenue / $count) : 0,
            'qris_percent' => $revenue > 0 ? round(($qris / $revenue) * 100, 1) : 0,
            'cash_percent' => $revenue > 0 ? round(($cash / $revenue) * 100, 1) : 0,
        ];
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
                'key' => $this->hariKey($hour->hari),
                'day' => $hour->hari_label,
                'open' => substr((string) $hour->jam_buka, 0, 5),
                'close' => substr((string) $hour->jam_tutup, 0, 5),
                'closed' => $hour->tutup,
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
            'about_image' => asset('images/menus/varya-signature.jpg'),
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

    private function menuKanonik(string $nama, int $harga): array
    {
        $kanonik = [
            'Varya Signature' => 22000,
            'Americano Street' => 18000,
            'Americano Street Cold' => 18000,
            'Matcha Creamy' => 24000,
            'Sunset Mocktail' => 26000,
            'Artisan Jasmine' => 18000,
            'Artisan Jasmine Tea' => 18000,
            'Caramel Macchiato' => 25000,
            'Salted Caramel Macchiato' => 25000,
            'Roti Bakar Mix' => 20000,
            'Truffle Fries' => 22000,
            'Bowl Sambal Matah' => 28000,
            'Mie Gacoan Varya Special' => 28000,
            'Almond Croissant' => 26000,
            'Butter French Croissant' => 26000,
            'Dimsum Mentai' => 24000,
            'Cold Brew Grit' => 30000,
            'Es Kopi Susu Gula Aren' => 22000,
            'Artisan Cappuccino' => 25000,
            'Ceremonial Matcha Latte' => 24000,
            'Basque Burnt Cheesecake' => 20000,
            'Lemon Tea Segar' => 18000,
            'Kopi Pandan' => 22000,
            'Chicken Wings Honey' => 22000,
            'Pisang Goreng Keju' => 26000,
            'Brown Sugar Latte' => 25000,
        ];

        $namaKanonik = match ($nama) {
            'Americano Street Cold' => 'Americano Street',
            'Artisan Jasmine Tea' => 'Artisan Jasmine',
            'Salted Caramel Macchiato' => 'Caramel Macchiato',
            'Mie Gacoan Varya Special' => 'Bowl Sambal Matah',
            'Butter French Croissant' => 'Almond Croissant',
            'Es Kopi Susu Gula Aren' => 'Varya Signature',
            'Artisan Cappuccino' => 'Caramel Macchiato',
            'Ceremonial Matcha Latte' => 'Matcha Creamy',
            'Basque Burnt Cheesecake' => 'Roti Bakar Mix',
            'Lemon Tea Segar' => 'Artisan Jasmine',
            'Kopi Pandan' => 'Varya Signature',
            'Chicken Wings Honey' => 'Truffle Fries',
            'Pisang Goreng Keju' => 'Almond Croissant',
            'Brown Sugar Latte' => 'Caramel Macchiato',
            default => $nama,
        };

        return [$namaKanonik, $kanonik[$namaKanonik] ?? $harga];
    }

    private function kodePesanan(string $seed): string
    {
        return strtoupper(substr(md5($seed), 0, 8));
    }
}

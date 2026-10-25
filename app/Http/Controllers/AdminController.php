<?php

namespace App\Http\Controllers;

use Carbon\CarbonImmutable;

/**
 * Tampilan admin / owner (front-end).
 *
 * Catatan tahap pengerjaan:
 * - Semua data pada controller ini adalah data contoh untuk kebutuhan tampilan.
 * - Belum ada autentikasi, query database, upload permanen, atau logika backend.
 * - Perubahan menu, promosi, dan informasi toko disimpan sementara di browser
 *   melalui resources/js/admin.js agar alur demo terasa utuh.
 */
class AdminController extends Controller
{
    public function dashboard()
    {
        $admin = $this->admin();
        $orders = array_map(static function (array $order): array {
            $payments = ['ORD-20261016-2JC9' => 'Tunai'];
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
            'peakHours' => $this->peakHours(),
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
            'config' => [
                'menus' => $menus,
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
            'featured' => false,
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
        return [
            'name' => 'Bagas Pratama',
            'initial' => 'BP',
            'role' => 'Admin / Owner',
        ];
    }

    private function categories(): array
    {
        return [
            ['slug' => 'kopi', 'label' => 'Kopi'],
            ['slug' => 'non-kopi', 'label' => 'Non-Kopi'],
            ['slug' => 'makanan', 'label' => 'Makanan'],
            ['slug' => 'camilan', 'label' => 'Camilan'],
        ];
    }

    private function menuData(): array
    {
        $rows = [
            [1, 'Varya Signature', 'kopi', 'Kopi', 20000, 'Double Ristretto, Susu Segar, Aren Bakar khas.', 'varya-signature.jpg', true, true],
            [2, 'Americano Street Cold', 'kopi', 'Kopi', 24000, 'Arabika Gayo & Toraja cold brew style pekat.', 'americano-street.jpg', true, false],
            [3, 'Artisan Cappuccino', 'kopi', 'Kopi', 28000, 'Double shot espresso Gayo dengan microfoam.', 'caramel-macchiato.jpg', true, false],
            [4, 'Salted Caramel Macchiato', 'kopi', 'Kopi', 32000, 'Perpaduan espresso kental, vanilla manis, dan caramel.', 'caramel-macchiato.jpg', true, false],
            [5, 'Ceremonial Matcha Latte', 'non-kopi', 'Non-Kopi', 30000, 'Bubuk matcha murni asal Uji, Kyoto yang ditaburi.', 'matcha-creamy.jpg', false, false],
            [6, 'Mie Gacoan Varya Special', 'makanan', 'Makanan', 18000, 'Sensasi pedas gurih pangit kriuk daun bawang.', 'bowl-sambal-matah.jpg', true, false],
            [7, 'Basque Burnt Cheesecake', 'camilan', 'Camilan', 35000, 'Slice cheesecake caramelized crust creamy.', 'almond-croissant.jpg', true, false],
            [8, 'Butter French Croissant', 'camilan', 'Camilan', 24000, 'Butter Prancis berlapis, dipanggang hingga renyah.', 'almond-croissant.jpg', true, false],
            [9, 'Es Kopi Susu Gula Aren', 'kopi', 'Kopi', 22000, 'Espresso, susu segar, dan gula aren cair.', 'varya-signature.jpg', true, false],
            [10, 'Cold Brew Grit', 'kopi', 'Kopi', 30000, 'Single origin Flores, cold brew 24 jam.', 'cold-brew-grit.jpg', false, false],
            [11, 'Artisan Jasmine Tea', 'non-kopi', 'Non-Kopi', 18000, 'Teh melati slow brew dengan aroma bunga.', 'artisan-jasmine.jpg', true, false],
            [12, 'Sunset Mocktail', 'non-kopi', 'Non-Kopi', 26000, 'Blood orange, soda, dan mint segar.', 'sunset-mocktail.jpg', true, false],
            [13, 'Truffle Fries', 'camilan', 'Camilan', 22000, 'Kentang renyah, parmesan, dan minyak truffle.', 'truffle-fries.jpg', true, false],
            [14, 'Bowl Sambal Matah', 'makanan', 'Makanan', 28000, 'Ayam krispi gurih dengan sambal matah segar.', 'bowl-sambal-matah.jpg', true, false],
            [15, 'Dimsum Mentai', 'makanan', 'Makanan', 24000, 'Isi 4 pcs, ayam udang, saus mentai torched.', 'dimsum-mentai.jpg', true, false],
            [16, 'Roti Bakar Mix', 'makanan', 'Makanan', 20000, 'Cokelat lumer, keju, dan roti panggang.', 'roti-bakar-mix.jpg', true, false],
            [17, 'Almond Croissant', 'camilan', 'Camilan', 26000, 'French butter layers dengan almond cream.', 'almond-croissant.jpg', true, false],
            [18, 'Matcha Creamy', 'non-kopi', 'Non-Kopi', 24000, 'Ceremonial grade Uji dengan oat milk creamy.', 'matcha-creamy.jpg', true, false],
            [19, 'Caramel Macchiato', 'kopi', 'Kopi', 25000, 'Vanilla milk, espresso, dan caramel.', 'caramel-macchiato.jpg', true, false],
            [20, 'Kopi Pandan', 'kopi', 'Kopi', 23000, 'Espresso, susu, dan sirup pandan wangi.', 'varya-signature.jpg', true, false],
            [21, 'Lemon Tea Segar', 'non-kopi', 'Non-Kopi', 16000, 'Teh hitam, lemon, dan madu.', 'artisan-jasmine.jpg', false, false],
            [22, 'Chicken Wings Honey', 'makanan', 'Makanan', 27000, 'Sayap ayam panggang dengan saus madu.', 'truffle-fries.jpg', true, false],
            [23, 'Pisang Goreng Keju', 'camilan', 'Camilan', 21000, 'Pisang goreng hangat, keju, dan susu.', 'almond-croissant.jpg', true, false],
            [24, 'Brown Sugar Latte', 'kopi', 'Kopi', 26000, 'Espresso, susu, brown sugar, dan boba.', 'caramel-macchiato.jpg', true, false],
        ];

        $skuCounters = [];
        $skuPrefixes = [
            'kopi' => 'KOP',
            'non-kopi' => 'NON',
            'makanan' => 'MKN',
            'camilan' => 'CML',
        ];

        return array_map(function (array $row) use (&$skuCounters, $skuPrefixes): array {
            [$id, $name, $category, $categoryLabel, $price, $description, $image, $available, $featured] = $row;

            $skuCounters[$category] = ($skuCounters[$category] ?? 0) + 1;

            return [
                'id' => $id,
                'name' => $name,
                'sku' => $skuPrefixes[$category].'-'.str_pad((string) $skuCounters[$category], 3, '0', STR_PAD_LEFT),
                'category' => $category,
                'category_label' => $categoryLabel,
                'price' => $price,
                'description' => $description,
                'image' => asset('images/menus/'.$image),
                'available' => $available,
                'featured' => $featured,
                'deleted' => false,
            ];
        }, $rows);
    }

    private function recentOrders(): array
    {
        return [
            [
                'code' => 'ORD-20261016-2JC9',
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
                'code' => 'ORD-20261016-1MEY',
                'customer' => 'Fathan Zada',
                'type' => 'dine_in',
                'type_label' => 'Di Tempat',
                'items' => 3,
                'portions' => 3,
                'total' => 105000,
                'status' => 'siap_saji',
                'status_label' => 'Siap Saji',
                'time' => '18 menit lalu',
            ],
            [
                'code' => 'ORD-20261016-4EFV',
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
                'code' => 'ORD-20261004-5UR7',
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
                'code' => 'ORD-20261016-7KQ2',
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
                'code' => 'ORD-20261016-9ZP4',
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
                'code' => 'ORD-20261016-3HXN',
                'customer' => 'Salsabila Putri',
                'type' => 'take_away',
                'type_label' => 'Bawa Pulang',
                'items' => 1,
                'portions' => 1,
                'total' => 32000,
                'status' => 'siap_saji',
                'status_label' => 'Siap Saji',
                'time' => '11 menit lalu',
            ],
            [
                'code' => 'ORD-20261016-6BVM',
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
                'code' => 'ORD-20261015-8QWE',
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
                'code' => 'ORD-20261015-2PLM',
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
    }

    private function popularMenus(): array
    {
        return [
            [
                'name' => 'Artisan Cappuccino',
                'price' => 28000,
                'note' => 'Ristretto Ganda',
                'sales' => '14x terjual',
                'tag' => 'Kopi Terlaris',
                'image' => asset('images/menus/caramel-macchiato.jpg'),
            ],
            [
                'name' => 'Salted Caramel Macchiato',
                'price' => 32000,
                'note' => 'Manis & Gurih',
                'sales' => '11x terjual',
                'tag' => 'Favorit Sore',
                'image' => asset('images/menus/caramel-macchiato.jpg'),
            ],
            [
                'name' => 'Ceremonial Matcha Latte',
                'price' => 30000,
                'note' => 'Murni Uji',
                'sales' => '9x terjual',
                'tag' => 'Non-Kopi Terlaris',
                'image' => asset('images/menus/matcha-creamy.jpg'),
            ],
            [
                'name' => 'Butter French Croissant',
                'price' => 24000,
                'note' => 'Baru Dipanggang',
                'sales' => '8x terjual',
                'tag' => 'Pasangan Kopi',
                'image' => asset('images/menus/almond-croissant.jpg'),
            ],
            [
                'name' => 'Basque Burnt Cheesecake',
                'price' => 35000,
                'note' => 'Kastard Lembut',
                'sales' => '6x terjual',
                'tag' => 'Hidangan Penutup Unggulan',
                'image' => asset('images/menus/almond-croissant.jpg'),
            ],
        ];
    }

    private function peakHours(): array
    {
        $hours = [
            ['label' => '15h', 'value' => 6],
            ['label' => '17h', 'value' => 18],
            ['label' => '19h', 'value' => 28],
            ['label' => '21h', 'value' => 42],
            ['label' => '23h', 'value' => 37],
            ['label' => '01h', 'value' => 22],
            ['label' => '03h', 'value' => 8],
        ];

        $max = max(array_column($hours, 'value'));

        return array_map(static fn (array $hour): array => [
            ...$hour,
            'height' => max(8, (int) round(($hour['value'] / $max) * 100)),
        ], $hours);
    }

    private function transactions(): array
    {
        $definitions = [
            // Oktober 2026 - total transaksi lunas: Rp 647.000.
            [
                'date' => '2026-10-16 03:37',
                'code' => 'TRX-20261016-DPV3',
                'order' => 'ORD-20261016-2JC9',
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
                'code' => 'TRX-20261006-UHCN',
                'order' => 'ORD-20261006-1MEY',
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
                'code' => 'TRX-20261006-4EFV',
                'order' => 'ORD-20261006-SXY1',
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
                'code' => 'TRX-20261004-FMJE',
                'order' => 'ORD-20261004-5UR7',
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
                'code' => 'TRX-20261001-DPYI',
                'order' => 'ORD-20261001-ACZP',
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
                'code' => 'TRX-20261001-001',
                'order' => 'ORD-20261001-001',
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
                'code' => 'TRX-20261002-002',
                'order' => 'ORD-20261002-002',
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
                'code' => 'TRX-20260929-002',
                'order' => 'ORD-20260929-002',
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
                'code' => 'TRX-20261016-PEND',
                'order' => 'ORD-20261016-PEND',
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
                'code' => 'TRX-20261016-PERIK',
                'order' => 'ORD-20261016-PERIK',
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
                'code' => 'TRX-20261015-GAGAL',
                'order' => 'ORD-20261015-GAGAL',
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
                'code' => 'TRX-20260928-003',
                'order' => 'ORD-20260928-003',
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
                'code' => 'TRX-20260925-004',
                'order' => 'ORD-20260925-004',
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
                'code' => 'TRX-20260922-005',
                'order' => 'ORD-20260922-005',
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
                'code' => 'TRX-20260920-006',
                'order' => 'ORD-20260920-006',
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
                'code' => 'TRX-20260918-007',
                'order' => 'ORD-20260918-007',
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
                'code' => 'TRX-20260915-008',
                'order' => 'ORD-20260915-008',
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
                'code' => 'TRX-20260912-009',
                'order' => 'ORD-20260912-009',
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
                'code' => 'TRX-20260828-010',
                'order' => 'ORD-20260828-010',
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
                'code' => 'TRX-20260825-011',
                'order' => 'ORD-20260825-011',
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
                'code' => 'TRX-20260822-012',
                'order' => 'ORD-20260822-012',
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
                'code' => 'TRX-20260818-013',
                'order' => 'ORD-20260818-013',
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
                'code' => 'TRX-20260814-014',
                'order' => 'ORD-20260814-014',
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
                'code' => 'TRX-20260810-015',
                'order' => 'ORD-20260810-015',
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
                'code' => 'TRX-20260806-016',
                'order' => 'ORD-20260806-016',
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
                'code' => 'TRX-20260802-017',
                'order' => 'ORD-20260802-017',
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
        $items = array_map(static fn (array $item): array => [
            'name' => $item[0],
            'qty' => $item[1],
            'price' => $item[2],
            'subtotal' => $item[1] * $item[2],
        ], $definition['items']);

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

        return [
            'id' => $definition['code'],
            'date_iso' => $date->format('Y-m-d'),
            'datetime' => $date->locale('id')->translatedFormat('d M Y, H:i').' WIB',
            'month' => $date->format('Y-m'),
            'month_label' => $date->locale('id')->translatedFormat('F Y'),
            'code' => $definition['code'],
            'order_code' => $definition['order'],
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
        return [
            [
                'id' => 1,
                'title' => 'Ngopi Sore Varya',
                'description' => 'Nikmati potongan harga untuk semua kopi susu setiap hari pukul 15.00-18.00.',
                'image' => asset('images/menus/caramel-macchiato.jpg'),
                'order' => 1,
                'period_start' => '2026-10-01',
                'period_end' => '2026-12-31',
                'active' => true,
            ],
            [
                'id' => 2,
                'title' => 'Paket Nongkrong',
                'description' => 'Dua kopi pilihan plus satu camilan favorit untuk teman ngobrol sore ini.',
                'image' => asset('images/menus/roti-bakar-mix.jpg'),
                'order' => 2,
                'period_start' => '2026-10-05',
                'period_end' => '2026-11-30',
                'active' => true,
            ],
            [
                'id' => 3,
                'title' => 'Matcha Season',
                'description' => 'Varian matcha creamy dengan harga spesial selama musim hujan.',
                'image' => asset('images/menus/matcha-creamy.jpg'),
                'order' => 3,
                'period_start' => '2026-10-10',
                'period_end' => null,
                'active' => true,
            ],
        ];
    }

    private function storeData(): array
    {
        return [
            'name' => 'Kedai Varya',
            'tagline' => 'Coffee & Eatery',
            'email' => 'varya.coffee@gmail.com',
            'description' => 'KEDAI KOPI VARYA lahir di sudut jalan perkotaan sebagai titik temu bagi para pecinta kopi, kreator visual, peselancar jalanan, dan musisi lokal. Kami memadukan denyut jalanan dengan teknik seduh presisi standar barista dunia. Setiap cangkir menyajikan biji kopi nusantara pilihan yang dipanggang segar, dipadukan gula aren otentik, susu artisanal, dan rempah alami lokal. Varya bukan sekadar tempat minum espresso cepat saji; ini adalah ruang kolaboratif terbuka untuk berdiskusi santai, mengerjakan tugas kreatif hingga larut, atau sekadar bertukar playlist favorit.',
            'address' => 'Jl. Wonokromo No. 16, Wonokromo, Kec. Wonokromo, Surabaya, Jawa Timur 60243',
            'maps_url' => 'https://maps.google.com/?q=Kedai+Kopi+Varya+Wonokromo',
            'landmark' => 'Dekat Jembatan Penyebrangan Wonokromo',
            'instagram' => '@kedaikopivarya',
            'phone' => '+62 812 3456 7890',
            'facilities' => ['WiFi Cepat', 'Colokan Listrik', 'Area Outdoor', 'Ruang Komunitas', 'Mushola', 'Parkir Motor'],
            'points' => [
                ['value' => '100%', 'label' => 'Kopi Lokal'],
                ['value' => '15+', 'label' => 'Kreasi Kopi & Non-Kopi'],
                ['value' => 'Setiap Hari', 'label' => 'Siap Melayani'],
            ],
            'hours' => [
                ['key' => 'senin', 'day' => 'Senin', 'open' => '15:00', 'close' => '03:00', 'closed' => false],
                ['key' => 'selasa', 'day' => 'Selasa', 'open' => '15:00', 'close' => '03:00', 'closed' => false],
                ['key' => 'rabu', 'day' => 'Rabu', 'open' => '15:00', 'close' => '03:00', 'closed' => false],
                ['key' => 'kamis', 'day' => 'Kamis', 'open' => '15:00', 'close' => '03:00', 'closed' => false],
                ['key' => 'jumat', 'day' => 'Jumat', 'open' => '15:00', 'close' => '03:00', 'closed' => false],
                ['key' => 'sabtu', 'day' => 'Sabtu', 'open' => '12:00', 'close' => '03:00', 'closed' => false],
                ['key' => 'minggu', 'day' => 'Minggu', 'open' => '12:00', 'close' => '02:00', 'closed' => false],
            ],
            'about_image' => asset('images/menus/varya-signature.jpg'),
            'last_updated' => 'Hari ini, 14:28 WIB',
        ];
    }
}


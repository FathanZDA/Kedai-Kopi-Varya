<?php

namespace App\Http\Controllers;

/**
 * Tampilan kasir (front-end).
 *
 * Catatan tahap pengerjaan:
 * - Seluruh data di controller ini adalah data contoh (mock) untuk kebutuhan
 *   tampilan. Belum ada query database, autentikasi, atau logika backend.
 * - Interaksi seperti keranjang, pembayaran tunai, dan status menu dijalankan
 *   di sisi klien melalui resources/js/kasir.js.
 */
class KasirController extends Controller
{
    public function buatPesanan()
    {
        return view('kasir.pesanan-baru', [
            'kasir' => $this->kasir(),
            'categories' => $this->categories(),
            'menus' => $this->menus(),
            'nextQueue' => $this->nextQueue(),
        ]);
    }

    public function pesananMasuk()
    {
        return view('kasir.pesanan-masuk', [
            'kasir' => $this->kasir(),
            'orders' => $this->orders(),
            'nextQueue' => $this->nextQueue(),
        ]);
    }

    public function kelolaMenu()
    {
        return view('kasir.kelola-menu', [
            'kasir' => $this->kasir(),
            'menus' => $this->menus(),
        ]);
    }

    private function kasir(): array
    {
        return [
            'name' => 'Dimas Pratama',
            'short_name' => 'Dimas',
            'initial' => 'DP',
            'role' => 'Kasir',
        ];
    }

    private function categories(): array
    {
        return [
            ['slug' => 'all', 'label' => 'Semua'],
            ['slug' => 'kopi', 'label' => 'Kopi'],
            ['slug' => 'teh', 'label' => 'Teh'],
            ['slug' => 'autentik', 'label' => 'Autentik'],
            ['slug' => 'makanan', 'label' => 'Makanan'],
        ];
    }

    private function menus(): array
    {
        return [
            [
                'id' => 1,
                'slug' => 'varya-signature',
                'name' => 'Varya Signature',
                'category' => 'kopi',
                'category_label' => 'Kopi',
                'price' => 22000,
                'description' => 'Aren, oat creamy, double shot.',
                'spec' => 'Espresso Blend • Roasted Arabica',
                'image' => 'images/menus/varya-signature.jpg',
                'featured' => true,
                'available' => true,
            ],
            [
                'id' => 2,
                'slug' => 'americano-street',
                'name' => 'Americano Street',
                'category' => 'kopi',
                'category_label' => 'Kopi',
                'price' => 18000,
                'description' => 'Single origin Flores, clean & bright.',
                'spec' => 'Double Shot • Water & Ice',
                'image' => 'images/menus/americano-street.jpg',
                'featured' => false,
                'available' => true,
            ],
            [
                'id' => 3,
                'slug' => 'matcha-creamy',
                'name' => 'Matcha Creamy',
                'category' => 'teh',
                'category_label' => 'Teh',
                'price' => 24000,
                'description' => 'Uji pure matcha, creamy milk.',
                'spec' => 'Ceremonial Grade Uji • Oat Milk',
                'image' => 'images/menus/matcha-creamy.jpg',
                'featured' => false,
                'available' => true,
            ],
            [
                'id' => 4,
                'slug' => 'sunset-mocktail',
                'name' => 'Sunset Mocktail',
                'category' => 'autentik',
                'category_label' => 'Autentik',
                'price' => 26000,
                'description' => 'Blood orange, soda, mint segar.',
                'spec' => 'Citrus • Soda • Fresh Mint',
                'image' => 'images/menus/sunset-mocktail.jpg',
                'featured' => false,
                'available' => true,
            ],
            [
                'id' => 5,
                'slug' => 'artisan-jasmine',
                'name' => 'Artisan Jasmine Tea',
                'category' => 'teh',
                'category_label' => 'Teh',
                'price' => 18000,
                'description' => 'Slow-brewed royal jasmine.',
                'spec' => 'Jasmine Tea • Slow Brewed',
                'image' => 'images/menus/artisan-jasmine.jpg',
                'featured' => false,
                'available' => true,
            ],
            [
                'id' => 6,
                'slug' => 'caramel-macchiato',
                'name' => 'Caramel Macchiato',
                'category' => 'kopi',
                'category_label' => 'Kopi',
                'price' => 25000,
                'description' => 'Vanilla milk, espresso, caramel.',
                'spec' => 'Espresso • Vanilla Milk • Caramel',
                'image' => 'images/menus/caramel-macchiato.jpg',
                'featured' => false,
                'available' => true,
            ],
            [
                'id' => 7,
                'slug' => 'roti-bakar-mix',
                'name' => 'Roti Bakar Mix',
                'category' => 'makanan',
                'category_label' => 'Makanan',
                'price' => 20000,
                'description' => 'Cokelat lumer + keju.',
                'spec' => 'Cokelat • Keju • Roti Panggang',
                'image' => 'images/menus/roti-bakar-mix.jpg',
                'featured' => false,
                'available' => true,
            ],
            [
                'id' => 8,
                'slug' => 'truffle-fries',
                'name' => 'Truffle Fries',
                'category' => 'makanan',
                'category_label' => 'Makanan',
                'price' => 22000,
                'description' => 'Parmesan snow, truffle oil.',
                'spec' => 'Parmesan • Truffle Oil • Crispy',
                'image' => 'images/menus/truffle-fries.jpg',
                'featured' => false,
                'available' => true,
            ],
            [
                'id' => 9,
                'slug' => 'bowl-sambal-matah',
                'name' => 'Bowl Sambal Matah',
                'category' => 'makanan',
                'category_label' => 'Makanan',
                'price' => 28000,
                'description' => 'Ayam krispi gurih, sambal matah.',
                'spec' => 'Crispy Chicken • Sambal Fresh',
                'image' => 'images/menus/bowl-sambal-matah.jpg',
                'featured' => false,
                'available' => true,
            ],
            [
                'id' => 10,
                'slug' => 'almond-croissant',
                'name' => 'Almond Croissant',
                'category' => 'makanan',
                'category_label' => 'Makanan',
                'price' => 26000,
                'description' => 'French butter layers, almond.',
                'spec' => 'French Butter • Almond Cream',
                'image' => 'images/menus/almond-croissant.jpg',
                'featured' => false,
                'available' => true,
            ],
            [
                'id' => 11,
                'slug' => 'dimsum-mentai',
                'name' => 'Dimsum Mentai',
                'category' => 'makanan',
                'category_label' => 'Makanan',
                'price' => 24000,
                'description' => 'Isi 4 pcs, torched mentai.',
                'spec' => 'Ayam Udang • Saus Mentai',
                'image' => 'images/menus/dimsum-mentai.jpg',
                'featured' => false,
                'available' => true,
            ],
            [
                'id' => 12,
                'slug' => 'cold-brew-grit',
                'name' => 'Cold Brew Grit',
                'category' => 'kopi',
                'category_label' => 'Kopi',
                'price' => 30000,
                'description' => 'Biji kopi habis dari roastery.',
                'spec' => 'Single Origin Flores • 24 Jam',
                'image' => 'images/menus/cold-brew-grit.jpg',
                'featured' => false,
                'available' => false,
            ],
        ];
    }

    private function orders(): array
    {
        $item = static fn (string $name, int $price, int $qty, ?string $note = null): array => [
            'name' => $name,
            'price' => $price,
            'qty' => $qty,
            'subtotal' => $price * $qty,
            'note' => $note,
        ];

        return [
            [
                'id' => 'ORD-001',
                'code' => 'V7K2M9QX',
                'queue' => '001',
                'customer' => 'Anjani',
                'type' => 'dine_in',
                'type_label' => 'Dine In',
                'source' => 'qr',
                'status' => 'antre',
                'payment' => 'qris',
                'payment_label' => 'QRIS BCA',
                'payment_status' => 'lunas',
                'total' => 90000,
                'created_at' => '23 Okt 2026 • 21:04 WIB',
                'midtrans_id' => 'a71f9c2e-4b80-4c11-9d0e-8a4b7f2c1d63',
                'notes' => 'Less Sugar',
                'items' => [
                    $item('Varya Signature', 22000, 1, 'Less Sugar (50%) • Normal Ice'),
                    $item('Almond Croissant', 26000, 1),
                    $item('Dimsum Mentai', 24000, 1),
                    $item('Americano Street', 18000, 1, 'Normal Ice • No Sugar'),
                ],
            ],
            [
                'id' => 'ORD-002',
                'code' => 'R4M8P2TZ',
                'queue' => '002',
                'customer' => 'Rian Pratama',
                'type' => 'take_away',
                'type_label' => 'Take Away',
                'source' => 'kasir',
                'status' => 'antre',
                'payment' => 'tunai',
                'payment_label' => 'Tunai',
                'payment_status' => 'lunas',
                'total' => 56000,
                'created_at' => '23 Okt 2026 • 21:10 WIB',
                'midtrans_id' => null,
                'cash_received' => 60000,
                'change' => 4000,
                'notes' => 'Less Sugar',
                'items' => [
                    $item('Americano Street', 18000, 2, 'Less Sugar'),
                    $item('Roti Bakar Mix', 20000, 1),
                ],
            ],
            [
                'id' => 'ORD-003',
                'code' => 'H9N3C6WL',
                'queue' => '003',
                'customer' => 'Salsabila Putri',
                'type' => 'take_away',
                'type_label' => 'Take Away',
                'source' => 'qr',
                'status' => 'diproses',
                'payment' => 'qris',
                'payment_label' => 'QRIS BCA',
                'payment_status' => 'lunas',
                'total' => 72000,
                'created_at' => '23 Okt 2026 • 21:14 WIB',
                'midtrans_id' => 'c2d84e1a-9f35-4a67-bc20-3e5f7a1d9b44',
                'notes' => 'Extra Spicy',
                'items' => [
                    $item('Matcha Creamy', 24000, 2),
                    $item('Dimsum Mentai', 24000, 1, 'Extra Spicy'),
                ],
            ],
            [
                'id' => 'ORD-004',
                'code' => 'B5T1Y8KD',
                'queue' => '004',
                'customer' => 'Bagas Pratama',
                'type' => 'dine_in',
                'type_label' => 'Dine In',
                'source' => 'qr',
                'status' => 'selesai',
                'payment' => 'qris',
                'payment_label' => 'QRIS BCA',
                'payment_status' => 'lunas',
                'total' => 56000,
                'created_at' => '23 Okt 2026 • 20:42 WIB',
                'midtrans_id' => 'f0a3b7d9-2c64-4e18-8a55-6d1f9c3b7e21',
                'notes' => 'Light Roast',
                'items' => [
                    $item('Cold Brew Grit', 30000, 1, 'Light Roast'),
                    $item('Almond Croissant', 26000, 1),
                ],
            ],
            [
                'id' => 'ORD-005',
                'code' => 'Q8S6J4AX',
                'queue' => null,
                'customer' => 'Nadia Kirana',
                'type' => 'dine_in',
                'type_label' => 'Dine In',
                'source' => 'qr',
                'status' => 'menunggu_pembayaran',
                'payment' => 'qris',
                'payment_label' => 'QRIS BCA',
                'payment_status' => 'menunggu',
                'total' => 66000,
                'created_at' => '23 Okt 2026 • 21:18 WIB',
                'expires_at' => '23 Okt 2026 • 21:33 WIB',
                'midtrans_id' => '7e1b5c8a-0d42-4f96-b3a1-9c8e2f6d4a10',
                'notes' => null,
                'items' => [
                    $item('Varya Signature', 22000, 2),
                    $item('Truffle Fries', 22000, 1),
                ],
            ],
            [
                'id' => 'ORD-006',
                'code' => 'M2D7F9VR',
                'queue' => null,
                'customer' => 'Fajar Ramadhan',
                'type' => 'take_away',
                'type_label' => 'Take Away',
                'source' => 'qr',
                'status' => 'menunggu_pembayaran',
                'payment' => 'qris',
                'payment_label' => 'QRIS BCA',
                'payment_status' => 'perlu_diperiksa',
                'total' => 52000,
                'created_at' => '23 Okt 2026 • 21:15 WIB',
                'expires_at' => '23 Okt 2026 • 21:30 WIB',
                'midtrans_id' => '3a9c6e2b-7f51-48d0-a6c4-1b5e9f7d2c88',
                'notes' => 'Tanpa Es',
                'items' => [
                    $item('Sunset Mocktail', 26000, 1, 'Tanpa Es'),
                    $item('Almond Croissant', 26000, 1),
                ],
            ],
            [
                'id' => 'ORD-007',
                'code' => 'K3W8L5NB',
                'queue' => '005',
                'customer' => 'Dewi Lestari',
                'type' => 'dine_in',
                'type_label' => 'Dine In',
                'source' => 'kasir',
                'status' => 'selesai',
                'payment' => 'tunai',
                'payment_label' => 'Tunai',
                'payment_status' => 'lunas',
                'total' => 45000,
                'created_at' => '23 Okt 2026 • 20:10 WIB',
                'midtrans_id' => null,
                'cash_received' => 50000,
                'change' => 5000,
                'notes' => null,
                'items' => [
                    $item('Caramel Macchiato', 25000, 1),
                    $item('Roti Bakar Mix', 20000, 1),
                ],
            ],
            [
                'id' => 'ORD-008',
                'code' => 'X6P1D9CM',
                'queue' => '006',
                'customer' => 'Yoga Pratama',
                'type' => 'take_away',
                'type_label' => 'Take Away',
                'source' => 'kasir',
                'status' => 'selesai',
                'payment' => 'tunai',
                'payment_label' => 'Tunai',
                'payment_status' => 'lunas',
                'total' => 42000,
                'created_at' => '23 Okt 2026 • 19:55 WIB',
                'midtrans_id' => null,
                'cash_received' => 50000,
                'change' => 8000,
                'notes' => 'Bungkus terpisah',
                'items' => [
                    $item('Americano Street', 18000, 1),
                    $item('Dimsum Mentai', 24000, 1, 'Bungkus terpisah'),
                ],
            ],
            [
                'id' => 'ORD-009',
                'code' => 'A4G2Z7YH',
                'queue' => '007',
                'customer' => 'Intan Permata',
                'type' => 'dine_in',
                'type_label' => 'Dine In',
                'source' => 'qr',
                'status' => 'selesai',
                'payment' => 'qris',
                'payment_label' => 'QRIS BCA',
                'payment_status' => 'lunas',
                'total' => 46000,
                'created_at' => '23 Okt 2026 • 19:30 WIB',
                'midtrans_id' => '9d5f2a7c-6b18-4e03-8c74-2f1a6b9d5e37',
                'notes' => null,
                'items' => [
                    $item('Varya Signature', 22000, 1),
                    $item('Matcha Creamy', 24000, 1),
                ],
            ],
        ];
    }

    private function nextQueue(): int
    {
        $highest = 0;

        foreach ($this->orders() as $order) {
            $highest = max($highest, (int) $order['queue']);
        }

        return $highest + 1;
    }
}

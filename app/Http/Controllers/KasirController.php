<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Menu;

/**
 * Tampilan kasir (front-end).
 *
 * Catatan tahap pengerjaan:
 * - Menu dan kategori sudah dibaca dari database.
 * - Pesanan dan pembayaran masih data demo sampai slice backend tersedia.
 * - Interaksi keranjang, pembayaran tunai, dan status menu masih dijalankan
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
                'id' => 'V7K2M9QX',
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
                'id' => 'R4M8P2TZ',
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
                'id' => 'H9N3C6WL',
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
                'id' => 'B5T1Y8KD',
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
                'id' => 'Q8S6J4AX',
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
                'id' => 'M2D7F9VR',
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
                'id' => 'K3W8L5NB',
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
                'id' => 'X6P1D9CM',
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
                'id' => 'A4G2Z7YH',
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

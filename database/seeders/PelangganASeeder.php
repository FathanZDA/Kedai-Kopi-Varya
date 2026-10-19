<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Promo;
use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

/**
 * Data awal untuk halaman publik (Pelanggan A).
 * Konten diambil dari file desain di folder /design.
 */
class PelangganASeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Kopi', 'slug' => 'kopi', 'sort_order' => 1],
            ['name' => 'Teh', 'slug' => 'teh', 'sort_order' => 2],
            ['name' => 'Autentik', 'slug' => 'autentik', 'sort_order' => 3],
            ['name' => 'Makanan', 'slug' => 'makanan', 'sort_order' => 4],
            ['name' => 'Cemilan', 'slug' => 'cemilan', 'sort_order' => 5],
        ];

        foreach ($categories as $category) {
            Category::create($category);
        }

        $categoryId = Category::pluck('id', 'slug');

        $menus = [
            ['slug' => 'varya-signature', 'name' => 'Varya Signature', 'category' => 'kopi', 'price' => 22000, 'is_featured' => true, 'is_available' => true,
                'description' => 'Espresso blend, gula aren murni & fresh milk gurih.'],
            ['slug' => 'americano-street', 'name' => 'Americano Street', 'category' => 'kopi', 'price' => 18000, 'is_featured' => false, 'is_available' => true,
                'description' => 'Double shot arabika Gayo dengan aroma fruity segar.'],
            ['slug' => 'matcha-creamy', 'name' => 'Matcha Creamy', 'category' => 'kopi', 'price' => 24000, 'is_featured' => false, 'is_available' => true,
                'description' => 'Pure Uji matcha Jepang berpadu susu kental lembut.'],
            ['slug' => 'sunset-mocktail', 'name' => 'Sunset Mocktail', 'category' => 'autentik', 'price' => 26000, 'is_featured' => false, 'is_available' => true,
                'description' => 'Cold brew, sari jeruk citrus, soda & rosemary segar.'],
            ['slug' => 'artisan-jasmine', 'name' => 'Artisan Jasmine', 'category' => 'teh', 'price' => 18000, 'is_featured' => false, 'is_available' => true,
                'description' => 'Seduhan daun teh Jawa dengan aroma melati alarmi.'],
            ['slug' => 'caramel-macchiato', 'name' => 'Caramel Macchiato', 'category' => 'kopi', 'price' => 25000, 'is_featured' => false, 'is_available' => true,
                'description' => 'Espresso bold dengan saus karamel gurih manis.'],
            ['slug' => 'roti-bakar-mix', 'name' => 'Roti Bakar Mix', 'category' => 'makanan', 'price' => 20000, 'is_featured' => false, 'is_available' => true,
                'description' => 'Roti tebal panggang krispi, limpahan cokelat & keju.'],
            ['slug' => 'truffle-fries', 'name' => 'Truffle Fries', 'category' => 'makanan', 'price' => 22000, 'is_featured' => false, 'is_available' => true,
                'description' => 'Kentang goreng renyah aroma truffle dan taburan keju.'],
            ['slug' => 'bowl-sambal-matah', 'name' => 'Bowl Sambal Matah', 'category' => 'makanan', 'price' => 28000, 'is_featured' => false, 'is_available' => true,
                'description' => 'Ayam krispi renyah, sambal matah segar & telur mata sapi.'],
            ['slug' => 'almond-croissant', 'name' => 'Almond Croissant', 'category' => 'cemilan', 'price' => 26000, 'is_featured' => false, 'is_available' => true,
                'description' => 'Pastry butter renyah dengan isian pasta almond legit.'],
            ['slug' => 'dimsum-mentai', 'name' => 'Dimsum Mentai', 'category' => 'makanan', 'price' => 24000, 'is_featured' => false, 'is_available' => true,
                'description' => 'Dimsum ayam udang lembut dengan saus mentai gurih.'],
            ['slug' => 'cold-brew-grit', 'name' => 'Cold Brew Grit', 'category' => 'kopi', 'price' => 30000, 'is_featured' => false, 'is_available' => false,
                'description' => 'Seduh dingin 24 jam single origin Flores Bajawa.'],
        ];

        foreach ($menus as $index => $menu) {
            Menu::create([
                'category_id' => $categoryId[$menu['category']],
                'name' => $menu['name'],
                'slug' => $menu['slug'],
                'price' => $menu['price'],
                'description' => $menu['description'],
                'image' => null,
                'is_featured' => $menu['is_featured'],
                'is_available' => $menu['is_available'],
                'sort_order' => $index + 1,
            ]);
        }

        // Tiga slide untuk carousel hero. Foto diambil dari tabel promos
        // supaya admin bisa menggantinya nanti lewat [AD-04].
        $promos = [
            [
                'title' => 'Kedai Varya',
                'subtitle' => 'Tempat Nongkrong // Komunitas // Ruang Kreasi',
                'description' => 'Ngobrol santai, musik mengendap, kopiReject yang tak terburu.',
                'image' => 'images/promos/hero-1.jpg',
                'badge_text' => 'Wajib Coba!',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Varya Mode Malam',
                'subtitle' => 'Event mingguan // Musik // Komunitas',
                'description' => 'Setiap malam Jumat, kedai berubah jadi panggung kecil.',
                'image' => 'images/promos/hero-2.jpg',
                'badge_text' => 'Setiap Jumat',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Ps Box Gratis',
                'subtitle' => 'Collab // Barista //本格',
                'description' => 'Scan QR di meja, pesan sendiri tanpa antre.',
                'image' => 'images/promos/hero-3.jpg',
                'badge_text' => 'Gratis!',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($promos as $promo) {
            Promo::create($promo);
        }

        StoreSetting::create([
            'name' => 'Kedai Kopi Varya',
            'tagline' => 'Tempat Nongkrong // Komunitas // Ruang Kreasi',
            'address' => 'Jl. Raya Wonokromo No.16, Wonokromo, Kec. Wonokromo, Surabaya, Jawa Timur 60243',
            'landmark' => 'Dekat Jembatan Peny Gravity',
            'maps_url' => 'https://maps.google.com/?q=Kedai+Kopi+Varya+Wonokromo+Surabaya',
            'instagram' => '@kedaikopivarya',
            'about_text' => 'KEDAI KOPI VARYA lahir di sudut jalan perkotaan sebagai titik temu bagi para '
                .'pecinta kopi, kreator visual, penjelajah jalan, dan musisi lokal. Kami memadukan '
                .'denyut jalan dengan teknik seduh presisi standar barista dunia. Setiap cangkir menyajikan '
                .'biji kopi nusantara pilihan yang dipanggang segar, dipadukan gula aren autentik, susu '
                .'artisan, dan rempah alami lokal. Varya bukan sekadar tempat minum espresso cepat saja; '
                .'ini adalah ruang kolaboratif terbuka untuk berdiskusi santai, mengerjakan tugas kreatif '
                .'hingga larut, atau sekadar bertukar playlist favorit.',
            'hours' => [
                'Senin' => '08.00 - 23.00',
                'Selasa' => '08.00 - 23.00',
                'Rabu' => '08.00 - 23.00',
                'Kamis' => '08.00 - 23.00',
                'Jumat' => '08.00 - 23.00',
                'Sabtu' => '08.00 - 23.00',
                'Minggu' => '08.00 - 23.00',
            ],
        ]);
    }
}
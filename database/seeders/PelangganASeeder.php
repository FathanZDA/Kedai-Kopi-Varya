<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Menu;
use App\Models\Promo;
use App\Models\StoreHour;
use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

/**
 * Data kanonik untuk halaman publik (Pelanggan A), Pelanggan B, kasir, dan
 * admin. Sumber menu mengikuti docs/DATABASE.md §10.2.
 */
class PelangganASeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['nama' => 'Kopi', 'slug' => 'kopi', 'urutan' => 1],
            ['nama' => 'Teh', 'slug' => 'teh', 'urutan' => 2],
            ['nama' => 'Autentik', 'slug' => 'autentik', 'urutan' => 3],
            ['nama' => 'Makanan', 'slug' => 'makanan', 'urutan' => 4],
            ['nama' => 'Cemilan', 'slug' => 'cemilan', 'urutan' => 5],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }

        $categoryId = Category::pluck('id', 'slug');

        $menus = [
            ['slug' => 'varya-signature', 'nama' => 'Varya Signature', 'category' => 'kopi', 'harga' => 22000, 'tersedia' => true,
                'deskripsi' => 'Espresso blend, gula aren murni & fresh milk gurih.', 'foto' => 'images/menus/varya-signature.jpg'],
            ['slug' => 'americano-street', 'nama' => 'Americano Street', 'category' => 'kopi', 'harga' => 18000, 'tersedia' => true,
                'deskripsi' => 'Double shot arabika Gayo dengan aroma fruity segar.', 'foto' => 'images/menus/americano-street.jpg'],
            ['slug' => 'matcha-creamy', 'nama' => 'Matcha Creamy', 'category' => 'teh', 'harga' => 24000, 'tersedia' => true,
                'deskripsi' => 'Pure Uji matcha Jepang berpadu susu kental lembut.', 'foto' => 'images/menus/matcha-creamy.jpg'],
            ['slug' => 'sunset-mocktail', 'nama' => 'Sunset Mocktail', 'category' => 'autentik', 'harga' => 26000, 'tersedia' => true,
                'deskripsi' => 'Cold brew, sari jeruk citrus, soda & rosemary segar.', 'foto' => 'images/menus/sunset-mocktail.jpg'],
            ['slug' => 'artisan-jasmine', 'nama' => 'Artisan Jasmine', 'category' => 'teh', 'harga' => 18000, 'tersedia' => true,
                'deskripsi' => 'Seduhan daun teh Jawa dengan aroma melati alami.', 'foto' => 'images/menus/artisan-jasmine.jpg'],
            ['slug' => 'caramel-macchiato', 'nama' => 'Caramel Macchiato', 'category' => 'kopi', 'harga' => 25000, 'tersedia' => true,
                'deskripsi' => 'Espresso bold dengan saus karamel gurih manis.', 'foto' => 'images/menus/caramel-macchiato.jpg'],
            ['slug' => 'roti-bakar-mix', 'nama' => 'Roti Bakar Mix', 'category' => 'makanan', 'harga' => 20000, 'tersedia' => true,
                'deskripsi' => 'Roti tebal panggang krispi, limpahan cokelat & keju.', 'foto' => 'images/menus/roti-bakar-mix.jpg'],
            ['slug' => 'truffle-fries', 'nama' => 'Truffle Fries', 'category' => 'makanan', 'harga' => 22000, 'tersedia' => true,
                'deskripsi' => 'Kentang goreng renyah aroma truffle dan taburan keju.', 'foto' => 'images/menus/truffle-fries.jpg'],
            ['slug' => 'bowl-sambal-matah', 'nama' => 'Bowl Sambal Matah', 'category' => 'makanan', 'harga' => 28000, 'tersedia' => true,
                'deskripsi' => 'Ayam krispi renyah, sambal matah segar & telur mata sapi.', 'foto' => 'images/menus/bowl-sambal-matah.jpg'],
            ['slug' => 'almond-croissant', 'nama' => 'Almond Croissant', 'category' => 'cemilan', 'harga' => 26000, 'tersedia' => true,
                'deskripsi' => 'Pastry butter renyah dengan isian pasta almond legit.', 'foto' => 'images/menus/almond-croissant.jpg'],
            ['slug' => 'dimsum-mentai', 'nama' => 'Dimsum Mentai', 'category' => 'makanan', 'harga' => 24000, 'tersedia' => true,
                'deskripsi' => 'Dimsum ayam udang lembut dengan saus mentai gurih.', 'foto' => 'images/menus/dimsum-mentai.jpg'],
            ['slug' => 'cold-brew-grit', 'nama' => 'Cold Brew Grit', 'category' => 'kopi', 'harga' => 30000, 'tersedia' => false,
                'deskripsi' => 'Seduh dingin 24 jam single origin Flores Bajawa.', 'foto' => 'images/menus/cold-brew-grit.jpg'],
        ];

        foreach ($menus as $index => $menu) {
            Menu::updateOrCreate(
                ['slug' => $menu['slug']],
                [
                    'category_id' => $categoryId[$menu['category']],
                    'nama' => $menu['nama'],
                    'harga' => $menu['harga'],
                    'deskripsi' => $menu['deskripsi'],
                    'foto' => $menu['foto'],
                    'tersedia' => $menu['tersedia'],
                    'urutan' => $index + 1,
                ],
            );
        }

        $promos = [
            [
                'judul' => 'Ngopi Sore Varya',
                'deskripsi' => 'Nikmati potongan harga untuk semua kopi susu setiap hari pukul 15.00-18.00.',
                'gambar' => 'images/menus/caramel-macchiato.jpg',
                'urutan' => 1,
                'aktif' => true,
                'mulai' => '2026-10-01',
                'berakhir' => '2026-12-31',
            ],
            [
                'judul' => 'Paket Nongkrong',
                'deskripsi' => 'Dua kopi pilihan plus satu camilan favorit untuk teman ngobrol sore ini.',
                'gambar' => 'images/menus/roti-bakar-mix.jpg',
                'urutan' => 2,
                'aktif' => true,
                'mulai' => '2026-10-05',
                'berakhir' => '2026-11-30',
            ],
            [
                'judul' => 'Matcha Season',
                'deskripsi' => 'Varian matcha creamy dengan harga spesial selama musim hujan.',
                'gambar' => 'images/menus/matcha-creamy.jpg',
                'urutan' => 3,
                'aktif' => true,
                'mulai' => '2026-10-10',
                'berakhir' => null,
            ],
        ];

        foreach ($promos as $promo) {
            Promo::updateOrCreate(['judul' => $promo['judul']], $promo);
        }

        StoreSetting::updateOrCreate(
            ['id' => 1],
            [
                'nama_toko' => 'Kedai Kopi Varya',
                'tagline' => 'Tempat Nongkrong // Komunitas // Ruang Kreasi',
                'sejarah' => 'KEDAI KOPI VARYA lahir di sudut jalan perkotaan sebagai titik temu bagi para '
                    .'pecinta kopi, kreator visual, penjelajah jalan, dan musisi lokal. Kami memadukan '
                    .'denyut jalan dengan teknik seduh presisi standar barista dunia. Setiap cangkir menyajikan '
                    .'biji kopi nusantara pilihan yang dipanggang segar, dipadukan gula aren autentik, susu '
                    .'artisan, dan rempah alami lokal. Varya bukan sekadar tempat minum espresso cepat saja; '
                    .'ini adalah ruang kolaboratif terbuka untuk berdiskusi santai, mengerjakan tugas kreatif '
                    .'hingga larut, atau sekadar bertukar playlist favorit.',
                'alamat' => 'Jl. Raya Wonokromo No.16, Wonokromo, Kec. Wonokromo, Surabaya, Jawa Timur 60243',
                'maps_url' => 'https://maps.google.com/?q=Kedai+Kopi+Varya+Wonokromo+Surabaya',
                'maps_embed' => null,
                'telepon' => '+62 812 3456 7890',
                'whatsapp' => '6281234567890',
                'instagram' => '@kedaikopivarya',
                'fasilitas' => [
                    'PS Box Gratis',
                    'WiFi Cepat',
                    'Colokan Listrik',
                    'Area Outdoor',
                    'Ruang Komunitas',
                    'Mushola',
                    'Parkir Motor',
                ],
            ],
        );

        foreach (range(0, 6) as $hari) {
            StoreHour::updateOrCreate(
                ['hari' => $hari],
                [
                    'jam_buka' => '08:00:00',
                    'jam_tutup' => '23:00:00',
                    'tutup' => false,
                ],
            );
        }
    }
}

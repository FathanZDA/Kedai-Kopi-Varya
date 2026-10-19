<?php

namespace Tests\Feature;

use Database\Seeders\PelangganASeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PelangganATest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PelangganASeeder::class);
    }

    public static function publicPages(): array
    {
        return [
            'beranda' => ['/'],
            'tentang' => ['/tentang-kami'],
            'menu' => ['/menu'],
            'lokasi' => ['/lokasi'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('publicPages')]
    public function test_halaman_publik_bisa_diakses(string $url): void
    {
        $this->get($url)->assertStatus(200);
    }

    public function test_beranda_menampilkan_hero_dan_kartu_menu(): void
    {
        $this->get('/')
            ->assertSee('Kedai Varya')
            ->assertSee('Tentang Kami')
            ->assertSee('data-hero-slide', escape: false)
            ->assertSee('data-menu-item', escape: false);
    }

    public function test_semua_produk_dari_desain_tampil(): void
    {
        $response = $this->get('/menu');

        foreach ([
            'Varya Signature', 'Americano Street', 'Matcha Creamy', 'Sunset Mocktail',
            'Artisan Jasmine', 'Caramel Macchiato', 'Roti Bakar Mix', 'Truffle Fries',
            'Bowl Sambal Matah', 'Almond Croissant', 'Dimsum Mentai', 'Cold Brew Grit',
        ] as $name) {
            $response->assertSee($name);
        }

        $this->assertSame(12, \App\Models\Menu::count());
    }

    public function test_harga_diformat_gaya_indonesia(): void
    {
        $menu = \App\Models\Menu::where('slug', 'varya-signature')->firstOrFail();

        $this->assertSame(22000, $menu->price);
        $this->assertSame('Rp 22.000', $menu->formatted_price);
    }

    public function test_produk_stok_habis_ditandai(): void
    {
        $this->get('/menu')->assertSee('Stok Habis');
        $this->assertFalse(\App\Models\Menu::where('slug', 'cold-brew-grit')->firstOrFail()->is_available);
    }

    public function test_beranda_menampilkan_semua_kategori(): void
    {
        $response = $this->get('/menu');

        foreach (['Kopi', 'Teh', 'Autentik', 'Makanan', 'Cemilan'] as $category) {
            $response->assertSee($category);
        }
    }

    public function test_pagination_nav_beranda_ditandai_aktif(): void
    {
        $response = $this->get('/tentang-kami');

        $response->assertSee('aria-current="page"', escape: false);
        $response->assertSee('Tentang Kami');
    }

    public function test_halaman_lokasi_menampilkan_peta_dan_tautan_maps(): void
    {
        $this->get('/lokasi')
            ->assertSee('openstreetmap.org', escape: false)
            ->assertSee('Buka di Google Maps');
    }
}
<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Promo;
use App\Models\StoreHour;
use App\Models\StoreSetting;
use App\Models\User;
use Database\Seeders\PelangganASeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
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
            'tentang' => ['/tentang'],
            'menu' => ['/menu'],
            'lokasi' => ['/lokasi'],
        ];
    }

    #[DataProvider('publicPages')]
    public function test_halaman_publik_bisa_diakses(string $url): void
    {
        $this->get($url)->assertStatus(200);
    }

    public function test_uri_tentang_lama_dialihkan_permanen(): void
    {
        $this->get('/tentang-kami')
            ->assertStatus(301)
            ->assertRedirect('/tentang');
    }

    public function test_beranda_menampilkan_hero_dan_kartu_menu(): void
    {
        $this->get('/')
            ->assertSee('Kedai Varya')
            ->assertSee('Tentang Kami')
            ->assertSee('data-hero-slide', escape: false)
            ->assertSee('data-menu-item', escape: false);
    }

    public function test_semua_produk_kanonik_tampil(): void
    {
        $response = $this->get('/menu');

        foreach ([
            'Varya Signature', 'Americano Street', 'Matcha Creamy', 'Sunset Mocktail',
            'Artisan Jasmine', 'Caramel Macchiato', 'Roti Bakar Mix', 'Truffle Fries',
            'Bowl Sambal Matah', 'Almond Croissant', 'Dimsum Mentai', 'Cold Brew Grit',
        ] as $name) {
            $response->assertSee($name);
        }

        $this->assertSame(12, Menu::count());
        $this->assertSame(5, Category::count());
    }

    public function test_matcha_creamy_berada_di_kategori_teh(): void
    {
        $menu = Menu::where('slug', 'matcha-creamy')->firstOrFail();

        $this->assertSame('Teh', $menu->category->nama);
    }

    public function test_harga_diformat_gaya_indonesia(): void
    {
        $menu = Menu::where('slug', 'varya-signature')->firstOrFail();

        $this->assertSame(22000, $menu->harga);
        $this->assertSame('Rp 22.000', $menu->formatted_price);
    }

    public function test_produk_stok_habis_ditandai(): void
    {
        $this->get('/menu')->assertSee('Stok Habis');
        $this->assertFalse(Menu::where('slug', 'cold-brew-grit')->firstOrFail()->tersedia);
    }

    public function test_beranda_menampilkan_semua_kategori(): void
    {
        $response = $this->get('/menu');

        foreach (['Kopi', 'Teh', 'Autentik', 'Makanan', 'Cemilan'] as $category) {
            $response->assertSee($category);
        }
    }

    public function test_navigasi_beranda_ditandai_aktif(): void
    {
        $response = $this->get('/tentang');

        $response->assertSee('aria-current="page"', escape: false);
        $response->assertSee('Tentang Kami');
    }

    public function test_halaman_lokasi_menampilkan_peta_dan_tautan_maps(): void
    {
        $this->get('/lokasi')
            ->assertSee('openstreetmap.org', escape: false)
            ->assertSee('Buka di Google Maps')
            ->assertSee('Jam Operasional');
    }

    public function test_promo_aktif_dan_jam_toko_dibaca_dari_database(): void
    {
        $this->assertSame(3, Promo::query()->active()->count());
        $this->assertSame(7, StoreHour::count());
        $this->assertSame('08.00 - 23.00', StoreSetting::instance()->hours_summary);
    }

    public function test_admin_dan_kasir_memakai_data_database(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $kasir = User::factory()->create(['role' => UserRole::Kasir]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertStatus(200)
            ->assertSee('Varya Signature');

        $this->actingAs($kasir)
            ->get('/kasir/pesanan/baru')
            ->assertStatus(200)
            ->assertSee('Varya Signature');
    }

    public function test_uri_admin_lama_dialihkan_ke_dashboard(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertStatus(301)
            ->assertRedirect('/admin/dashboard');
    }
}

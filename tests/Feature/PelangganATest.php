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

    public static function standaloneNavigationPages(): array
    {
        return [
            'tentang' => ['/tentang', 'tentang'],
            'menu' => ['/menu', 'menu'],
            'lokasi' => ['/lokasi', 'lokasi'],
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

    public function test_landing_page_dan_halaman_pesan_menampilkan_lima_slide_sesuai_urutan(): void
    {
        Promo::query()->delete();

        foreach (range(1, 5) as $urutan) {
            Promo::create([
                'judul' => "Promo Slide {$urutan}",
                'deskripsi' => null,
                'gambar' => null,
                'urutan' => $urutan,
                'aktif' => true,
                'mulai' => null,
                'berakhir' => null,
            ]);
        }

        foreach (['/', '/pesan'] as $url) {
            $response = $this->get($url)->assertOk();

            $this->assertSame(5, substr_count($response->getContent(), 'data-hero-slide='));
            $response->assertSee('data-hero-slide="4"', false);
            $response->assertSeeInOrder([
                'Promo Slide 1',
                'Promo Slide 2',
                'Promo Slide 3',
                'Promo Slide 4',
                'Promo Slide 5',
            ]);
        }
    }

    public function test_beranda_memakai_navigasi_anchor_dan_id_beranda(): void
    {
        $beranda = route('pelanggan-a.beranda');
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('id="beranda"', false)
            ->assertSee('href="'.$beranda.'#beranda"', false)
            ->assertSee('href="'.$beranda.'#tentang"', false)
            ->assertSee('href="'.$beranda.'#menu"', false)
            ->assertSee('href="'.$beranda.'#lokasi"', false);

        $this->assertMatchesRegularExpression(
            '/data-nav-section="beranda"[^>]*aria-current="page"/s',
            $response->getContent()
        );
    }

    #[DataProvider('standaloneNavigationPages')]
    public function test_halaman_mandiri_menandai_navigasi_sesuai_halaman(string $url, string $section): void
    {
        $response = $this->get($url);

        $response->assertOk();

        $this->assertMatchesRegularExpression(
            '/data-nav-section="'.preg_quote($section, '/').'"[^>]*aria-current="page"/s',
            $response->getContent()
        );
        $this->assertDoesNotMatchRegularExpression(
            '/data-nav-section="beranda"[^>]*aria-current="page"/s',
            $response->getContent()
        );
    }

    public function test_beranda_menampilkan_pratinjau_empat_menu_dan_tombol_lihat_semua(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('data-menu-preview-limit="4"', false)
            ->assertSee('data-menu-more', false)
            ->assertSee('data-menu-index="0"', false)
            ->assertSee('data-menu-index="11"', false)
            ->assertSee('Lihat Semua Menu');

        // Tombol pratinjau harus berupa <button>, bukan tautan ke halaman /menu.
        $this->assertMatchesRegularExpression('/<button[^>]*data-menu-more/', $response->getContent());
        $this->assertDoesNotMatchRegularExpression(
            '/<a\b[^>]*href="'.preg_quote(route('pelanggan-a.menu'), '/').'"[^>]*>\s*Lihat Semua Menu/s',
            $response->getContent()
        );
    }

    public function test_halaman_menu_penuh_tanpa_batas_pratinjau(): void
    {
        $response = $this->get('/menu');

        $response->assertOk()
            ->assertSee('data-menu-preview-limit="0"', false)
            ->assertSee('data-menu-index="11"', false)
            ->assertDontSee('data-menu-more', false);
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
        $this->assertSame(3, Category::count());
    }

    public function test_matcha_creamy_berada_di_kategori_bukan_kopi(): void
    {
        $menu = Menu::where('slug', 'matcha-creamy')->firstOrFail();

        $this->assertSame('Bukan Kopi', $menu->category->nama);
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

    public function test_filter_menu_memakai_kelompok_publik(): void
    {
        $response = $this->get('/menu');

        $response->assertOk()
            ->assertSee('data-filter="all"', false)
            ->assertSee('data-filter="kopi"', false)
            ->assertSee('data-filter="bukan-kopi"', false)
            ->assertSee('data-filter="makanan-camilan"', false)
            ->assertSee('Bukan Kopi')
            ->assertSee('Makanan & Camilan');

        $html = $response->getContent();

        $this->assertSame(4, substr_count($html, 'data-category="kopi"'));
        $this->assertSame(3, substr_count($html, 'data-category="bukan-kopi"'));
        $this->assertSame(5, substr_count($html, 'data-category="makanan-camilan"'));
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

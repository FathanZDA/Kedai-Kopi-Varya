<?php

namespace Tests\Feature;

use App\Models\Menu;
use Database\Seeders\PelangganASeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PelangganBMenuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PelangganASeeder::class);
    }

    public function test_beranda_pesan_memakai_pratinjau_empat_menu_dengan_tombol_toggle(): void
    {
        $response = $this->get(route('pelanggan-b.beranda'));

        $response->assertOk()
            ->assertSee('data-menu-preview-limit="4"', false)
            ->assertSee('data-menu-more', false)
            ->assertSee('data-menu-index="0"', false)
            ->assertSee('data-menu-index="11"', false)
            ->assertSee('data-filter="bukan-kopi"', false)
            ->assertSee('Makanan & Camilan')
            ->assertSee('Lihat Semua Menu');

        $this->assertMatchesRegularExpression('/<button[^>]*data-menu-more/', $response->getContent());
        $this->assertDoesNotMatchRegularExpression(
            '/<a\b[^>]*href="#menu"[^>]*>\s*Lihat Semua Menu/s',
            $response->getContent()
        );
    }

    public function test_seluruh_menu_dirender_di_beranda_pesan_sebelum_diperluas(): void
    {
        $response = $this->get(route('pelanggan-b.beranda'));

        $response->assertOk();

        $html = $response->getContent();

        // Semua kartu tetap dirender server; JS hanya menyembunyikan kartu ke-5 dan seterusnya.
        $this->assertSame(12, substr_count($html, 'data-menu-item'));
        $this->assertSame(12, Menu::count());
        $this->assertSame(11, substr_count($html, 'data-add-to-cart'));

        foreach (Menu::all() as $menu) {
            $response->assertSee($menu->nama);
        }
    }
}

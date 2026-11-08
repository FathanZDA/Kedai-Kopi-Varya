<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Menu;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SederhanakanKategoriMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_migrasi_menggabungkan_kategori_lama_dan_memindahkan_menu(): void
    {
        $kopi = Category::create(['nama' => 'Kopi', 'slug' => 'kopi', 'urutan' => 1]);
        $teh = Category::create(['nama' => 'Teh', 'slug' => 'teh', 'urutan' => 2]);
        $autentik = Category::create(['nama' => 'Autentik', 'slug' => 'autentik', 'urutan' => 3]);
        $makanan = Category::create(['nama' => 'Makanan', 'slug' => 'makanan', 'urutan' => 4]);
        $cemilan = Category::create(['nama' => 'Cemilan', 'slug' => 'cemilan', 'urutan' => 5]);

        $matcha = $this->buatMenu($teh, 'Matcha Creamy', 'matcha-creamy');
        $sunset = $this->buatMenu($autentik, 'Sunset Mocktail', 'sunset-mocktail');
        $roti = $this->buatMenu($makanan, 'Roti Bakar Mix', 'roti-bakar-mix');
        $almond = $this->buatMenu($cemilan, 'Almond Croissant', 'almond-croissant');
        $softDeleted = $this->buatMenu($teh, 'Menu Lama', 'menu-lama');
        $softDeleted->delete();

        $this->migrasi()->up();

        $bukanKopi = Category::query()->where('slug', 'bukan-kopi')->firstOrFail();
        $makananCamilan = Category::query()->where('slug', 'makanan-camilan')->firstOrFail();

        $this->assertSame('Bukan Kopi', $bukanKopi->nama);
        $this->assertSame(2, $bukanKopi->urutan);
        $this->assertSame('Makanan & Camilan', $makananCamilan->nama);
        $this->assertSame(3, $makananCamilan->urutan);

        $this->assertSame($bukanKopi->id, $matcha->fresh()->category_id);
        $this->assertSame($bukanKopi->id, $sunset->fresh()->category_id);
        $this->assertSame($makananCamilan->id, $roti->fresh()->category_id);
        $this->assertSame($makananCamilan->id, $almond->fresh()->category_id);
        $this->assertSame($bukanKopi->id, Menu::withTrashed()->findOrFail($softDeleted->id)->category_id);

        $this->assertSame($kopi->id, Category::query()->where('slug', 'kopi')->value('id'));
        $this->assertSame(3, Category::count());
        $this->assertDatabaseMissing('categories', ['slug' => 'teh']);
        $this->assertDatabaseMissing('categories', ['slug' => 'autentik']);
        $this->assertDatabaseMissing('categories', ['slug' => 'makanan']);
        $this->assertDatabaseMissing('categories', ['slug' => 'cemilan']);
    }

    public function test_migrasi_aman_dijalankan_dua_kali(): void
    {
        Category::create(['nama' => 'Kopi', 'slug' => 'kopi', 'urutan' => 1]);
        Category::create(['nama' => 'Teh', 'slug' => 'teh', 'urutan' => 2]);
        Category::create(['nama' => 'Autentik', 'slug' => 'autentik', 'urutan' => 3]);
        Category::create(['nama' => 'Makanan', 'slug' => 'makanan', 'urutan' => 4]);
        Category::create(['nama' => 'Cemilan', 'slug' => 'cemilan', 'urutan' => 5]);

        $migration = $this->migrasi();
        $migration->up();
        $migration->up();

        $this->assertSame(3, Category::count());
    }

    private function buatMenu(Category $category, string $nama, string $slug): Menu
    {
        return Menu::create([
            'category_id' => $category->id,
            'nama' => $nama,
            'slug' => $slug,
            'harga' => 20000,
            'deskripsi' => 'Menu uji migrasi.',
            'tersedia' => true,
            'urutan' => 1,
        ]);
    }

    private function migrasi(): object
    {
        return require database_path('migrations/2026_11_07_000001_sederhanakan_kategori_menu.php');
    }
}

<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KasirMenuAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    private User $kasir;

    private Menu $kopi;

    private Menu $teh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kasir = User::factory()->create(['role' => UserRole::Kasir]);

        $category = Category::create(['nama' => 'Kopi', 'slug' => 'kopi', 'urutan' => 1]);

        $this->kopi = Menu::create([
            'category_id' => $category->id,
            'nama' => 'Americano Street',
            'slug' => 'americano-street',
            'harga' => 18000,
            'tersedia' => true,
            'urutan' => 1,
        ]);

        $this->teh = Menu::create([
            'category_id' => $category->id,
            'nama' => 'Matcha Creamy',
            'slug' => 'matcha-creamy',
            'harga' => 24000,
            'tersedia' => false,
            'urutan' => 2,
        ]);
    }

    public function test_halaman_kelola_menu_menampilkan_ketersediaan_dari_database(): void
    {
        $this->actingAs($this->kasir)
            ->get(route('kasir.menu.index'))
            ->assertOk()
            ->assertSee('Americano Street')
            ->assertSee('Matcha Creamy')
            ->assertSee('data-available="1"', false)
            ->assertSee('data-available="0"', false)
            ->assertSee('Set Habis')
            ->assertSee('Set Tersedia');
    }

    public function test_kasir_dapat_menandai_menu_habis(): void
    {
        $this->actingAs($this->kasir)
            ->patchJson(route('kasir.menu.availability', $this->kopi), ['tersedia' => false])
            ->assertOk()
            ->assertJsonPath('data.id', $this->kopi->id)
            ->assertJsonPath('data.tersedia', false)
            ->assertJsonPath('data.tersedia_label', 'Habis');

        $this->assertFalse($this->kopi->fresh()->tersedia);
    }

    public function test_kasir_dapat_menandai_menu_tersedia_kembali(): void
    {
        $this->actingAs($this->kasir)
            ->patchJson(route('kasir.menu.availability', $this->teh), ['tersedia' => true])
            ->assertOk()
            ->assertJsonPath('data.tersedia', true)
            ->assertJsonPath('data.tersedia_label', 'Tersedia');

        $this->assertTrue($this->teh->fresh()->tersedia);
    }

    public function test_perubahan_hanya_menyentuh_kolom_tersedia(): void
    {
        $sebelum = $this->kopi->only(['nama', 'harga', 'category_id', 'slug']);

        $this->actingAs($this->kasir)
            ->patchJson(route('kasir.menu.availability', $this->kopi), [
                'tersedia' => false,
                'nama' => 'Nama Diubah',
                'harga' => 1000,
            ])
            ->assertOk();

        $sesudah = $this->kopi->fresh();
        $this->assertSame($sebelum, $sesudah->only(['nama', 'harga', 'category_id', 'slug']));
        $this->assertFalse($sesudah->tersedia);
    }

    public function test_status_ketersediaan_wajib_diisi(): void
    {
        $this->actingAs($this->kasir)
            ->patchJson(route('kasir.menu.availability', $this->kopi), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tersedia');

        $this->assertTrue($this->kopi->fresh()->tersedia);
    }

    public function test_status_ketersediaan_harus_boolean(): void
    {
        $this->actingAs($this->kasir)
            ->patchJson(route('kasir.menu.availability', $this->kopi), ['tersedia' => 'mungkin'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('tersedia');

        $this->assertTrue($this->kopi->fresh()->tersedia);
    }

    public function test_menu_yang_sudah_dihapus_tidak_bisa_diubah(): void
    {
        $this->kopi->delete();

        $this->actingAs($this->kasir)
            ->patchJson(route('kasir.menu.availability', $this->kopi), ['tersedia' => false])
            ->assertNotFound();
    }

    public function test_admin_tidak_boleh_mengubah_ketersediaan_lewat_route_kasir(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->patchJson(route('kasir.menu.availability', $this->kopi), ['tersedia' => false])
            ->assertForbidden();

        $this->assertTrue($this->kopi->fresh()->tersedia);
    }

    public function test_tamu_ditolak_dan_diarahkan_ke_login(): void
    {
        $this->patchJson(route('kasir.menu.availability', $this->kopi), ['tersedia' => false])
            ->assertUnauthorized();

        $this->assertTrue($this->kopi->fresh()->tersedia);
    }
}

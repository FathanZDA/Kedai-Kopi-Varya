<?php

namespace Tests\Feature;

use App\Enums\MetodePembayaran;
use App\Enums\SumberPesanan;
use App\Enums\TipePesanan;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminMenuCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * PNG 1x1 valid agar validasi `image` tidak membutuhkan ekstensi GD.
     */
    private const PNG_KECIL = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==';

    private User $admin;

    private Category $kopi;

    private Menu $menu;

    /**
     * Foto unggahan yang sudah ada sebelum test (mis. foto menu asli hasil
     * unggahan admin). Jangan pernah dihapus oleh pembersihan test.
     *
     * @var list<string>
     */
    private array $berkasAwal = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->berkasAwal = glob(public_path('images/menus/unggahan-*')) ?: [];

        $this->kopi = Category::create(['nama' => 'Kopi', 'slug' => 'kopi', 'urutan' => 1]);
        Category::create(['nama' => 'Bukan Kopi', 'slug' => 'bukan-kopi', 'urutan' => 2]);
        Category::create(['nama' => 'Makanan & Camilan', 'slug' => 'makanan-camilan', 'urutan' => 3]);

        $this->menu = Menu::create([
            'category_id' => $this->kopi->id,
            'nama' => 'Americano Street',
            'slug' => 'americano-street',
            'harga' => 18000,
            'deskripsi' => 'Espresso dingin.',
            'tersedia' => true,
            'urutan' => 1,
        ]);
    }

    protected function tearDown(): void
    {
        // Hanya hapus berkas yang dibuat oleh test ini; foto menu asli admin
        // di direktori yang sama tidak boleh tersentuh.
        foreach (array_diff(glob(public_path('images/menus/unggahan-*')) ?: [], $this->berkasAwal) as $berkas) {
            @unlink($berkas);
        }

        parent::tearDown();
    }

    public function test_admin_dapat_menambah_menu_dengan_foto(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.menu.store'), [
            'nama' => 'Kopi Pandan',
            'category_id' => $this->kopi->id,
            'harga' => 22000,
            'deskripsi' => 'Kopi susu dengan aroma pandan.',
            'tersedia' => '1',
            'foto' => UploadedFile::fake()->createWithContent('kopi-pandan.png', base64_decode(self::PNG_KECIL)),
        ]);

        $response->assertRedirect(route('admin.menu.index'))
            ->assertSessionHas('status', 'Menu "Kopi Pandan" berhasil ditambahkan.');

        $menu = Menu::query()->where('nama', 'Kopi Pandan')->firstOrFail();

        $this->assertSame('kopi-pandan', $menu->slug);
        $this->assertSame(22000, $menu->harga);
        $this->assertTrue($menu->tersedia);
        $this->assertSame(2, $menu->urutan);
        $this->assertNotNull($menu->foto);
        $this->assertStringStartsWith('images/menus/unggahan-', $menu->foto);
        $this->assertFileExists(public_path($menu->foto));
    }

    public function test_tambah_menu_tanpa_foto_tetap_tersimpan(): void
    {
        $this->actingAs($this->admin)->post(route('admin.menu.store'), [
            'nama' => 'Teh Melati',
            'category_id' => $this->kopi->id,
            'harga' => 15000,
            'deskripsi' => '',
            'tersedia' => '0',
        ])->assertRedirect(route('admin.menu.index'));

        $menu = Menu::query()->where('nama', 'Teh Melati')->firstOrFail();

        $this->assertNull($menu->foto);
        $this->assertFalse($menu->tersedia);
    }

    public function test_slug_unik_dibuat_otomatis_untuk_nama_yang_sama(): void
    {
        $this->actingAs($this->admin)->post(route('admin.menu.store'), [
            'nama' => 'Americano Street',
            'category_id' => $this->kopi->id,
            'harga' => 19000,
            'tersedia' => '1',
        ])->assertRedirect(route('admin.menu.index'));

        $baru = Menu::query()->where('harga', 19000)->firstOrFail();

        $this->assertSame('americano-street-2', $baru->slug);
    }

    public function test_validasi_menu_ditolak_di_server(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.menu.store'), [
            'nama' => '',
            'category_id' => 9999,
            'harga' => 0,
            'deskripsi' => str_repeat('a', 301),
            'tersedia' => '1',
            'foto' => UploadedFile::fake()->createWithContent('catatan.txt', 'bukan gambar'),
        ]);

        $response->assertSessionHasErrors(['nama', 'category_id', 'harga', 'deskripsi', 'foto']);
        $this->assertSame(1, Menu::withTrashed()->count());
    }

    public function test_admin_dapat_mengubah_menu_tanpa_mengubah_slug_dan_snapshot_pesanan(): void
    {
        $order = Order::create([
            'kode_pesanan' => 'QR12CD34',
            'nama_pelanggan' => 'Anjani',
            'tipe_pesanan' => TipePesanan::DineIn,
            'sumber' => SumberPesanan::Qr,
            'status_pesanan' => 'antre',
            'metode_pembayaran' => MetodePembayaran::Qris,
            'status_pembayaran' => 'lunas',
            'total' => 18000,
            'nomor_antrean' => 1,
            'tanggal_antrean' => now()->toDateString(),
        ]);

        $item = $order->items()->create([
            'menu_id' => $this->menu->id,
            'nama_menu' => 'Americano Street',
            'harga_satuan' => 18000,
            'jumlah' => 1,
            'subtotal' => 18000,
        ]);

        $this->actingAs($this->admin)->put(route('admin.menu.update', $this->menu), [
            'nama' => 'Americano Street Baru',
            'category_id' => $this->kopi->id,
            'harga' => 21000,
            'deskripsi' => 'Resep baru.',
            'tersedia' => '0',
        ])->assertRedirect(route('admin.menu.index'));

        $this->menu->refresh();

        $this->assertSame('Americano Street Baru', $this->menu->nama);
        $this->assertSame('americano-street', $this->menu->slug);
        $this->assertSame(21000, $this->menu->harga);
        $this->assertFalse($this->menu->tersedia);
        $this->assertSame(18000, $item->refresh()->harga_satuan);
        $this->assertSame('Americano Street', $item->nama_menu);
    }

    public function test_ubah_menu_dengan_foto_baru_menghapus_unggahan_lama(): void
    {
        $this->actingAs($this->admin)->post(route('admin.menu.store'), [
            'nama' => 'Kopi Pandan',
            'category_id' => $this->kopi->id,
            'harga' => 22000,
            'tersedia' => '1',
            'foto' => UploadedFile::fake()->createWithContent('kopi-pandan.png', base64_decode(self::PNG_KECIL)),
        ]);

        $menu = Menu::query()->where('nama', 'Kopi Pandan')->firstOrFail();
        $fotoLama = $menu->foto;

        $this->assertFileExists(public_path($fotoLama));

        $this->actingAs($this->admin)->put(route('admin.menu.update', $menu), [
            'nama' => 'Kopi Pandan',
            'category_id' => $this->kopi->id,
            'harga' => 24000,
            'tersedia' => '1',
            'foto' => UploadedFile::fake()->createWithContent('kopi-pandan-2.png', base64_decode(self::PNG_KECIL)),
        ])->assertRedirect(route('admin.menu.index'));

        $menu->refresh();

        $this->assertNotSame($fotoLama, $menu->foto);
        $this->assertFileDoesNotExist(public_path($fotoLama));
        $this->assertFileExists(public_path($menu->foto));
    }

    public function test_menu_dihapus_soft_delete_dan_hilang_dari_halaman_publik_dan_kasir(): void
    {
        $kasir = User::factory()->create(['role' => UserRole::Kasir]);

        $this->actingAs($this->admin)
            ->delete(route('admin.menu.destroy', $this->menu))
            ->assertRedirect(route('admin.menu.index'))
            ->assertSessionHas('status', 'Menu "Americano Street" berhasil dihapus.');

        $this->assertSoftDeleted('menus', ['id' => $this->menu->id]);
        $this->assertDatabaseCount('menus', 1);

        $this->actingAs($this->admin)
            ->get(route('admin.menu.index'))
            ->assertOk()
            ->assertDontSee('data-menu-id="'.$this->menu->id.'"', false);

        $this->get(route('pelanggan-a.menu'))
            ->assertOk()
            ->assertDontSee('Americano Street');

        $this->actingAs($kasir)
            ->get(route('kasir.menu.index'))
            ->assertOk()
            ->assertDontSee('Americano Street');
    }

    public function test_halaman_form_tambah_dan_ubah_menampilkan_data_dari_database(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.menu.create'))
            ->assertOk()
            ->assertSee('Tambah Menu Baru');

        $this->actingAs($this->admin)
            ->get(route('admin.menu.edit', $this->menu))
            ->assertOk()
            ->assertSee('Americano Street')
            ->assertSee('value="18000"', false)
            ->assertSee('name="nama"', false)
            ->assertSee('name="foto"', false)
            ->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_halaman_kelola_menu_memakai_tiga_kategori_baru(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.menu.index'))
            ->assertOk()
            ->assertSee('data-admin-menu-category="kopi"', false)
            ->assertSee('data-admin-menu-category="bukan-kopi"', false)
            ->assertSee('data-admin-menu-category="makanan-camilan"', false)
            ->assertDontSee('data-admin-menu-category="teh"', false)
            ->assertDontSee('data-admin-menu-category="autentik"', false)
            ->assertDontSee('data-admin-menu-category="makanan"', false)
            ->assertDontSee('data-admin-menu-category="cemilan"', false);

        $this->actingAs($this->admin)
            ->get(route('admin.menu.create'))
            ->assertOk()
            ->assertSee('Bukan Kopi')
            ->assertSee('Makanan & Camilan');
    }

    public function test_admin_dapat_mengubah_ketersediaan_dari_tabel(): void
    {
        $this->actingAs($this->admin)
            ->patchJson(route('admin.menu.availability', $this->menu), ['tersedia' => false])
            ->assertOk()
            ->assertJsonPath('data.id', $this->menu->id)
            ->assertJsonPath('data.tersedia', false);

        $this->assertFalse($this->menu->refresh()->tersedia);
    }

    public function test_kasir_tidak_dapat_mengelola_katalog_menu(): void
    {
        $kasir = User::factory()->create(['role' => UserRole::Kasir]);

        $this->actingAs($kasir)
            ->post(route('admin.menu.store'), [
                'nama' => 'Menu Kasir',
                'category_id' => $this->kopi->id,
                'harga' => 10000,
                'tersedia' => '1',
            ])
            ->assertForbidden();

        $this->actingAs($kasir)
            ->patchJson(route('admin.menu.availability', $this->menu), ['tersedia' => false])
            ->assertForbidden();

        $this->assertTrue($this->menu->refresh()->tersedia);
        $this->assertDatabaseMissing('menus', ['nama' => 'Menu Kasir']);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->post(route('admin.menu.store'), [])->assertRedirect(route('login'));
        $this->get(route('admin.menu.create'))->assertRedirect(route('login'));
    }

    public function test_penanda_terlaris_tersimpan_lewat_form_menu(): void
    {
        $payload = [
            'nama' => 'Americano Street',
            'category_id' => $this->kopi->id,
            'harga' => 18000,
            'deskripsi' => 'Espresso dingin.',
            'tersedia' => '1',
        ];

        $this->actingAs($this->admin)
            ->put(route('admin.menu.update', $this->menu), $payload + ['terlaris' => '1'])
            ->assertRedirect(route('admin.menu.index'));

        $this->assertTrue($this->menu->refresh()->terlaris);
        $this->assertTrue($this->menu->tersedia);

        $this->actingAs($this->admin)
            ->put(route('admin.menu.update', $this->menu), $payload + ['terlaris' => '0'])
            ->assertRedirect(route('admin.menu.index'));

        $this->assertFalse($this->menu->refresh()->terlaris);
    }

    public function test_badge_terlaris_hanya_tampil_untuk_menu_yang_ditandai(): void
    {
        $this->get(route('pelanggan-a.menu'))
            ->assertOk()
            ->assertDontSee('Terlaris');

        $this->menu->update(['terlaris' => true]);

        $this->get(route('pelanggan-a.menu'))
            ->assertOk()
            ->assertSee('Terlaris')
            ->assertSee('Rp 18.000');
    }
}

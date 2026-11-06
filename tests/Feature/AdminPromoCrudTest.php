<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Promo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminPromoCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * PNG 1x1 valid agar validasi `image` tidak membutuhkan ekstensi GD.
     */
    private const PNG_KECIL = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==';

    private User $admin;

    private Promo $promo;

    /**
     * Berkas unggahan yang sudah ada sebelum test (mis. gambar promo asli
     * hasil unggahan admin). Jangan pernah dihapus oleh pembersihan test.
     *
     * @var list<string>
     */
    private array $berkasAwal = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->berkasAwal = glob(public_path('images/promos/unggahan-promo-*')) ?: [];

        $this->promo = Promo::create([
            'judul' => 'Ngopi Sore Varya',
            'deskripsi' => 'Potongan harga kopi susu sore hari.',
            'gambar' => 'images/menus/caramel-macchiato.jpg',
            'urutan' => 1,
            'aktif' => true,
            'mulai' => null,
            'berakhir' => null,
        ]);
    }

    protected function tearDown(): void
    {
        // Hanya hapus berkas yang dibuat oleh test ini; gambar promo asli
        // admin di direktori yang sama tidak boleh tersentuh.
        foreach (array_diff(glob(public_path('images/promos/unggahan-promo-*')) ?: [], $this->berkasAwal) as $berkas) {
            @unlink($berkas);
        }

        parent::tearDown();
    }

    public function test_halaman_promosi_menampilkan_data_dari_database(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.promos'))
            ->assertOk()
            ->assertSee('Ngopi Sore Varya')
            ->assertSee('name="judul"', false)
            ->assertSee('name="gambar"', false)
            ->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_admin_dapat_menambah_promosi_dengan_gambar(): void
    {
        $this->actingAs($this->admin)->post(route('admin.promos.store'), [
            'judul' => 'Diskon Akhir Pekan',
            'deskripsi' => 'Sepuluh persen untuk semua minuman.',
            'urutan' => 2,
            'aktif' => '1',
            'mulai' => '2026-11-01',
            'berakhir' => '2026-11-30',
            'gambar' => UploadedFile::fake()->createWithContent('promo.png', base64_decode(self::PNG_KECIL)),
        ])->assertRedirect(route('admin.promos'))
            ->assertSessionHas('status', 'Promosi "Diskon Akhir Pekan" berhasil ditambahkan.');

        $promo = Promo::query()->where('judul', 'Diskon Akhir Pekan')->firstOrFail();

        $this->assertSame(2, $promo->urutan);
        $this->assertTrue($promo->aktif);
        $this->assertSame('2026-11-01', $promo->mulai?->toDateString());
        $this->assertSame('2026-11-30', $promo->berakhir?->toDateString());
        $this->assertStringStartsWith('images/promos/unggahan-promo-', $promo->gambar);
        $this->assertFileExists(public_path($promo->gambar));
    }

    public function test_tambah_promosi_tanpa_gambar_tetap_tersimpan(): void
    {
        $this->actingAs($this->admin)->post(route('admin.promos.store'), [
            'judul' => 'Promo Tanpa Gambar',
            'urutan' => 3,
            'aktif' => '0',
        ])->assertRedirect(route('admin.promos'));

        $promo = Promo::query()->where('judul', 'Promo Tanpa Gambar')->firstOrFail();

        $this->assertNull($promo->gambar);
        $this->assertFalse($promo->aktif);
    }

    public function test_validasi_promosi_ditolak_di_server(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.promos.store'), [
            'judul' => '',
            'deskripsi' => str_repeat('a', 221),
            'urutan' => 0,
            'aktif' => '1',
            'mulai' => '2026-11-30',
            'berakhir' => '2026-11-01',
            'gambar' => UploadedFile::fake()->createWithContent('promo.txt', 'bukan gambar'),
        ]);

        $response->assertSessionHasErrors(['judul', 'deskripsi', 'urutan', 'berakhir', 'gambar']);
        $this->assertSame(1, Promo::query()->count());
    }

    public function test_admin_dapat_mengubah_promosi_dan_mengganti_gambar(): void
    {
        $this->actingAs($this->admin)->post(route('admin.promos.store'), [
            'judul' => 'Promo Bergambar',
            'urutan' => 4,
            'aktif' => '1',
            'gambar' => UploadedFile::fake()->createWithContent('promo.png', base64_decode(self::PNG_KECIL)),
        ]);

        $promo = Promo::query()->where('judul', 'Promo Bergambar')->firstOrFail();
        $gambarLama = $promo->gambar;

        $this->assertFileExists(public_path($gambarLama));

        $this->actingAs($this->admin)->put(route('admin.promos.update', $promo), [
            'judul' => 'Promo Bergambar Baru',
            'deskripsi' => 'Deskripsi baru.',
            'urutan' => 5,
            'aktif' => '0',
            'mulai' => '2026-11-02',
            'berakhir' => '2026-12-02',
            'gambar' => UploadedFile::fake()->createWithContent('promo-2.png', base64_decode(self::PNG_KECIL)),
        ])->assertRedirect(route('admin.promos'))
            ->assertSessionHas('status', 'Promosi "Promo Bergambar Baru" berhasil diperbarui.');

        $promo->refresh();

        $this->assertSame('Promo Bergambar Baru', $promo->judul);
        $this->assertSame(5, $promo->urutan);
        $this->assertFalse($promo->aktif);
        $this->assertNotSame($gambarLama, $promo->gambar);
        $this->assertFileDoesNotExist(public_path($gambarLama));
        $this->assertFileExists(public_path($promo->gambar));
    }

    public function test_admin_dapat_mengubah_status_tampil(): void
    {
        $this->actingAs($this->admin)
            ->patchJson(route('admin.promos.status', $this->promo), ['aktif' => false])
            ->assertOk()
            ->assertJsonPath('data.id', $this->promo->id)
            ->assertJsonPath('data.aktif', false)
            ->assertJsonPath('message', 'Promosi "Ngopi Sore Varya" dinonaktifkan.');

        $this->assertFalse($this->promo->refresh()->aktif);
    }

    public function test_promosi_nonaktif_dan_kedaluwarsa_tidak_tampil_di_landing_page(): void
    {
        $this->get(route('pelanggan-a.beranda'))->assertOk()->assertSee('Ngopi Sore Varya');

        $this->actingAs($this->admin)
            ->patchJson(route('admin.promos.status', $this->promo), ['aktif' => false])
            ->assertOk();

        $this->get(route('pelanggan-a.beranda'))->assertOk()->assertDontSee('Ngopi Sore Varya');

        $this->promo->update(['aktif' => true, 'berakhir' => now()->subDay()->toDateString()]);

        $this->get(route('pelanggan-a.beranda'))->assertOk()->assertDontSee('Ngopi Sore Varya');
    }

    public function test_admin_dapat_menghapus_promosi_dan_gambar_unggahannya(): void
    {
        $this->actingAs($this->admin)->post(route('admin.promos.store'), [
            'judul' => 'Promo Sementara',
            'urutan' => 6,
            'aktif' => '1',
            'gambar' => UploadedFile::fake()->createWithContent('promo.png', base64_decode(self::PNG_KECIL)),
        ]);

        $promo = Promo::query()->where('judul', 'Promo Sementara')->firstOrFail();
        $gambar = $promo->gambar;

        $this->assertFileExists(public_path($gambar));

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.promos.destroy', $promo))
            ->assertOk()
            ->assertJsonPath('data.id', $promo->id);

        $this->assertDatabaseMissing('promos', ['id' => $promo->id]);
        $this->assertFileDoesNotExist(public_path($gambar));
    }

    public function test_kasir_tidak_dapat_mengelola_promosi(): void
    {
        $kasir = User::factory()->create(['role' => UserRole::Kasir]);

        $this->actingAs($kasir)->post(route('admin.promos.store'), [
            'judul' => 'Promo Kasir',
            'urutan' => 1,
            'aktif' => '1',
        ])->assertForbidden();

        $this->actingAs($kasir)
            ->patchJson(route('admin.promos.status', $this->promo), ['aktif' => false])
            ->assertForbidden();

        $this->assertDatabaseMissing('promos', ['judul' => 'Promo Kasir']);
        $this->assertTrue($this->promo->refresh()->aktif);
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->post(route('admin.promos.store'), [])->assertRedirect(route('login'));
        $this->delete(route('admin.promos.destroy', $this->promo))->assertRedirect(route('login'));
    }
}

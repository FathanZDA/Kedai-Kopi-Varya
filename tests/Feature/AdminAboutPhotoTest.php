<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AdminAboutPhotoTest extends TestCase
{
    use RefreshDatabase;

    /**
     * PNG 1x1 valid agar validasi `image` tidak membutuhkan ekstensi GD.
     */
    private const PNG_KECIL = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFAAH/q842iQAAAABJRU5ErkJggg==';

    private User $admin;

    /**
     * Berkas unggahan yang sudah ada sebelum test (mis. foto asli hasil
     * unggahan admin). Jangan pernah dihapus oleh pembersihan test.
     *
     * @var list<string>
     */
    private array $berkasAwal = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
        $this->berkasAwal = glob(public_path('images/toko/unggahan-*')) ?: [];
    }

    protected function tearDown(): void
    {
        // Hanya hapus berkas yang dibuat oleh test ini; unggahan asli admin
        // di direktori yang sama tidak boleh tersentuh.
        foreach (array_diff(glob(public_path('images/toko/unggahan-*')) ?: [], $this->berkasAwal) as $berkas) {
            @unlink($berkas);
        }

        parent::tearDown();
    }

    private function unggah(string $nama = 'kedai.png')
    {
        return $this->actingAs($this->admin)->post(route('admin.store.foto-tentang.store'), [
            'foto' => UploadedFile::fake()->createWithContent($nama, base64_decode(self::PNG_KECIL)),
        ]);
    }

    public function test_admin_dapat_mengunggah_foto_tentang(): void
    {
        $this->unggah()
            ->assertRedirect(route('admin.promos'))
            ->assertSessionHas('status', 'Foto tentang kami berhasil disimpan.');

        $store = StoreSetting::query()->firstOrFail();

        $this->assertNotNull($store->foto_tentang);
        $this->assertStringStartsWith('images/toko/unggahan-', $store->foto_tentang);
        $this->assertFileExists(public_path($store->foto_tentang));
    }

    public function test_foto_tampil_di_halaman_tentang_publik(): void
    {
        $this->unggah();

        $store = StoreSetting::query()->firstOrFail();

        $this->get(route('pelanggan-a.tentang'))
            ->assertOk()
            ->assertSee(asset($store->foto_tentang), false);
    }

    public function test_foto_tampil_di_beranda_pelanggan_b(): void
    {
        $this->unggah();

        $store = StoreSetting::query()->firstOrFail();

        $this->get(route('pelanggan-b.beranda'))
            ->assertOk()
            ->assertSee(asset($store->foto_tentang), false);
    }

    public function test_panel_promosi_menampilkan_pesan_saat_foto_belum_dipilih(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.promos'))
            ->followingRedirects()
            ->post(route('admin.store.foto-tentang.store'), [])
            ->assertOk()
            ->assertSee('Pilih foto terlebih dahulu.');

        $this->assertNull(StoreSetting::query()->first()?->foto_tentang);
    }

    public function test_tanpa_foto_halaman_tentang_memakai_placeholder(): void
    {
        $this->get(route('pelanggan-a.tentang'))
            ->assertOk()
            ->assertDontSee('Suasana Kedai Kopi Varya', false)
            ->assertDontSee('images/toko/unggahan-', false);
    }

    public function test_unggah_baru_menghapus_berkas_lama(): void
    {
        $this->unggah('pertama.png');
        $lama = StoreSetting::query()->firstOrFail()->foto_tentang;

        $this->unggah('kedua.png');
        $baru = StoreSetting::query()->firstOrFail()->foto_tentang;

        $this->assertNotSame($lama, $baru);
        $this->assertFileDoesNotExist(public_path($lama));
        $this->assertFileExists(public_path($baru));
    }

    public function test_berkas_bukan_gambar_ditolak(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.promos'))
            ->post(route('admin.store.foto-tentang.store'), [
                'foto' => UploadedFile::fake()->create('dokumen.pdf', 20, 'application/pdf'),
            ])
            ->assertRedirect(route('admin.promos'))
            ->assertSessionHasErrors('foto');

        $this->assertNull(StoreSetting::query()->first()?->foto_tentang);
    }

    public function test_admin_dapat_menghapus_foto_tentang(): void
    {
        $this->unggah();
        $path = StoreSetting::query()->firstOrFail()->foto_tentang;

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.store.foto-tentang.destroy'))
            ->assertOk()
            ->assertJsonPath('data.about_image', null);

        $this->assertNull(StoreSetting::query()->firstOrFail()->foto_tentang);
        $this->assertFileDoesNotExist(public_path($path));
    }

    public function test_panel_promosi_menampilkan_status_foto(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.promos'))
            ->assertOk()
            ->assertSee('Tidak Tampil');

        $this->unggah();

        $this->actingAs($this->admin)
            ->get(route('admin.promos'))
            ->assertOk()
            ->assertSee('Tampil');
    }

    public function test_kasir_tidak_boleh_mengunggah_foto(): void
    {
        $kasir = User::factory()->create(['role' => UserRole::Kasir]);

        $this->actingAs($kasir)
            ->post(route('admin.store.foto-tentang.store'), [
                'foto' => UploadedFile::fake()->createWithContent('kedai.png', base64_decode(self::PNG_KECIL)),
            ])
            ->assertForbidden();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->post(route('admin.store.foto-tentang.store'))
            ->assertRedirect(route('login'));
    }
}

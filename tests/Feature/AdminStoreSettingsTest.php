<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\StoreHour;
use App\Models\StoreSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStoreSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);

        // Migration align_schema_with_prd sudah mengisi 7 baris jam toko,
        // jadi data bawaan dibersihkan dulu agar test memakai data sendiri.
        StoreHour::query()->delete();
        StoreSetting::query()->delete();

        StoreSetting::create([
            'id' => 1,
            'nama_toko' => 'Kedai Kopi Varya',
            'tagline' => 'Tempat Nongkrong // Komunitas // Ruang Kreasi',
            'sejarah' => 'Kedai kopi kecil di sudut Wonokromo.',
            'alamat' => 'Jl. Raya Wonokromo No.16, Surabaya',
            'maps_url' => 'https://maps.google.com/?q=Kedai+Kopi+Varya',
            'maps_embed' => null,
            'telepon' => '+62 812 3456 7890',
            'whatsapp' => '6281234567890',
            'instagram' => '@kedaikopivarya',
            'fasilitas' => ['PS Box Gratis', 'WiFi Cepat'],
        ]);

        foreach (range(0, 6) as $hari) {
            StoreHour::create([
                'hari' => $hari,
                'jam_buka' => '08:00:00',
                'jam_tutup' => '23:00:00',
                'tutup' => false,
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $ganti
     * @return array<string, mixed>
     */
    private function payload(array $ganti = []): array
    {
        $jam = [];

        foreach (range(0, 6) as $hari) {
            $jam[$hari] = [
                'buka_jam' => '08',
                'buka_menit' => '00',
                'tutup_jam' => '23',
                'tutup_menit' => '00',
            ];
        }

        return array_replace_recursive([
            'nama_toko' => 'Kedai Kopi Varya',
            'tagline' => 'Tempat Nongkrong // Komunitas // Ruang Kreasi',
            'sejarah' => 'Kedai kopi kecil di sudut Wonokromo.',
            'alamat' => 'Jl. Raya Wonokromo No.16, Surabaya',
            'maps_url' => 'https://maps.google.com/?q=Kedai+Kopi+Varya',
            'maps_embed' => '',
            'telepon' => '+62 812 3456 7890',
            'whatsapp' => '6281234567890',
            'instagram' => 'kedaikopivarya',
            'fasilitas' => 'PS Box Gratis, WiFi Cepat',
            'jam' => $jam,
        ], $ganti);
    }

    public function test_halaman_toko_menampilkan_data_dari_database(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.store'))
            ->assertOk()
            ->assertSee('Kedai Kopi Varya')
            ->assertSee('name="nama_toko"', false)
            ->assertSee('name="jam[0][buka_jam]"', false)
            ->assertSee('name="fasilitas"', false)
            ->assertSee('action="'.route('admin.store.update').'"', false)
            ->assertSee('name="_method" value="PUT"', false);
    }

    public function test_jadwal_tujuh_hari_langsung_terlihat_tanpa_details(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.store'))
            ->assertOk()
            ->assertSee('Jam Operasional')
            ->assertSee('Jadwal per hari')
            ->assertSee('Hari Ini: 08:00 – 23:00 WIB (Buka)')
            ->assertDontSee('Atur jadwal per hari');

        $this->assertSame(7, substr_count($response->getContent(), 'data-store-hour-row'));

        foreach (range(0, 6) as $hari) {
            $response
                ->assertSee('name="jam['.$hari.'][buka_jam]"', false)
                ->assertSee('name="jam['.$hari.'][buka_menit]"', false)
                ->assertSee('name="jam['.$hari.'][tutup_jam]"', false)
                ->assertSee('name="jam['.$hari.'][tutup_menit]"', false)
                ->assertSee('name="jam['.$hari.'][libur]"', false);
        }
    }

    public function test_admin_dapat_menyimpan_profil_toko_dan_kontak(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.store.update'), $this->payload([
                'nama_toko' => 'Varya Coffee House',
                'tagline' => 'Kopi // Komunitas',
                'sejarah' => 'Sejarah baru kedai Varya.',
                'alamat' => 'Jl. Baru No. 1, Surabaya',
                'telepon' => '+62 811 000 111',
                'whatsapp' => '62811000111',
                'instagram' => '@varyacoffee',
                'fasilitas' => 'WiFi Cepat, Mushola, WiFi Cepat',
                'maps_url' => 'https://maps.google.com/?q=Varya',
            ]))
            ->assertRedirect(route('admin.store'))
            ->assertSessionHas('status', 'Informasi toko berhasil disimpan.');

        $store = StoreSetting::query()->firstOrFail();

        $this->assertSame('Varya Coffee House', $store->nama_toko);
        $this->assertSame('Kopi // Komunitas', $store->tagline);
        $this->assertSame('Sejarah baru kedai Varya.', $store->sejarah);
        $this->assertSame('Jl. Baru No. 1, Surabaya', $store->alamat);
        $this->assertSame('+62 811 000 111', $store->telepon);
        $this->assertSame('62811000111', $store->whatsapp);
        $this->assertSame('@varyacoffee', $store->instagram);
        $this->assertSame(['WiFi Cepat', 'Mushola'], $store->fasilitas);
        $this->assertSame(1, StoreSetting::query()->count());
    }

    public function test_jam_operasional_tujuh_hari_tersimpan_termasuk_hari_libur(): void
    {
        $jam = [];

        foreach (range(0, 6) as $hari) {
            $jam[$hari] = [
                'buka_jam' => '09',
                'buka_menit' => '00',
                'tutup_jam' => '22',
                'tutup_menit' => '00',
            ];
        }

        $jam[0] = [
            'buka_jam' => '10',
            'buka_menit' => '15',
            'tutup_jam' => '20',
            'tutup_menit' => '30',
        ];
        $jam[1] = [
            'buka_jam' => '09',
            'buka_menit' => '00',
            'tutup_jam' => '22',
            'tutup_menit' => '00',
            'libur' => '1',
        ];

        $this->actingAs($this->admin)
            ->put(route('admin.store.update'), $this->payload(['jam' => $jam]))
            ->assertRedirect(route('admin.store'));

        $senin = StoreHour::query()->where('hari', 1)->firstOrFail();
        $minggu = StoreHour::query()->where('hari', 0)->firstOrFail();

        $this->assertTrue($senin->tutup);
        $this->assertNull($senin->jam_buka);
        $this->assertNull($senin->jam_tutup);
        $this->assertFalse($minggu->tutup);
        $this->assertSame('10:15:00', $minggu->jam_buka);
        $this->assertSame('20:30:00', $minggu->jam_tutup);
        $this->assertSame(7, StoreHour::query()->count());
    }

    public function test_perubahan_langsung_tampil_di_halaman_publik(): void
    {
        $jam = [];

        foreach (range(0, 6) as $hari) {
            $jam[$hari] = [
                'buka_jam' => '06',
                'buka_menit' => '45',
                'tutup_jam' => '21',
                'tutup_menit' => '15',
            ];
        }

        $this->actingAs($this->admin)
            ->put(route('admin.store.update'), $this->payload([
                'nama_toko' => 'Varya Baru',
                'sejarah' => 'Cerita baru tentang Varya.',
                'alamat' => 'Jl. Publik Baru No. 9',
                'fasilitas' => 'PS Box Gratis, Ruang Outdoor',
                'jam' => $jam,
            ]))
            ->assertRedirect(route('admin.store'));

        $this->get(route('pelanggan-a.tentang'))
            ->assertOk()
            ->assertSee('Cerita baru tentang Varya.');

        $this->get(route('pelanggan-a.lokasi'))
            ->assertOk()
            ->assertSee('Varya Baru')
            ->assertSee('Jl. Publik Baru No. 9')
            ->assertSee('Ruang Outdoor')
            ->assertSee('06.45 - 21.15');
    }

    public function test_validasi_ditolak_di_server(): void
    {
        $response = $this->actingAs($this->admin)
            ->put(route('admin.store.update'), $this->payload([
                'nama_toko' => '',
                'maps_url' => 'bukan-url',
                'maps_embed' => 'bukan-url',
                'telepon' => 'telepon aneh',
                'fasilitas' => implode(', ', array_map(fn (int $i): string => 'Fasilitas '.$i, range(1, 21))),
            ]));

        $response->assertSessionHasErrors(['nama_toko', 'maps_url', 'maps_embed', 'telepon', 'fasilitas']);
        $this->assertSame('Kedai Kopi Varya', StoreSetting::query()->firstOrFail()->nama_toko);
    }

    public function test_hari_tanpa_jam_buka_dan_tutup_ditolak_kecuali_libur(): void
    {
        $jam = [];

        foreach (range(0, 6) as $hari) {
            $jam[$hari] = [
                'buka_jam' => '08',
                'buka_menit' => '00',
                'tutup_jam' => '23',
                'tutup_menit' => '00',
            ];
        }

        $jam[3] = [
            'buka_jam' => '',
            'buka_menit' => '',
            'tutup_jam' => '',
            'tutup_menit' => '',
        ];

        $this->actingAs($this->admin)
            ->put(route('admin.store.update'), $this->payload(['jam' => $jam]))
            ->assertSessionHasErrors(['jam.3.buka_jam']);

        $jam[3] = [
            'buka_jam' => '',
            'buka_menit' => '',
            'tutup_jam' => '',
            'tutup_menit' => '',
            'libur' => '1',
        ];

        $this->actingAs($this->admin)
            ->put(route('admin.store.update'), $this->payload(['jam' => $jam]))
            ->assertSessionHasNoErrors();

        $rabu = StoreHour::query()->where('hari', 3)->firstOrFail();

        $this->assertTrue($rabu->tutup);
        $this->assertNull($rabu->jam_buka);
    }

    public function test_field_opsional_boleh_dikosongkan(): void
    {
        $this->actingAs($this->admin)
            ->put(route('admin.store.update'), $this->payload([
                'tagline' => '',
                'sejarah' => '',
                'alamat' => '',
                'maps_url' => '',
                'maps_embed' => '',
                'telepon' => '',
                'whatsapp' => '',
                'instagram' => '',
                'fasilitas' => '',
            ]))
            ->assertRedirect(route('admin.store'))
            ->assertSessionHasNoErrors();

        $store = StoreSetting::query()->firstOrFail();

        $this->assertSame('', $store->tagline);
        $this->assertNull($store->sejarah);
        $this->assertNull($store->alamat);
        $this->assertNull($store->instagram);
        $this->assertSame([], $store->fasilitas);
        $this->assertSame('Kedai Kopi Varya', $store->nama_toko);
    }

    public function test_tamu_diarahkan_ke_login_dan_kasir_ditolak(): void
    {
        $this->put(route('admin.store.update'), $this->payload())
            ->assertRedirect(route('login'));

        $kasir = User::factory()->create(['role' => UserRole::Kasir]);

        $this->actingAs($kasir)
            ->put(route('admin.store.update'), $this->payload())
            ->assertForbidden();
    }

    public function test_nilai_isian_tidak_hilang_saat_validasi_gagal(): void
    {
        $this->actingAs($this->admin)
            ->from(route('admin.store'))
            ->put(route('admin.store.update'), $this->payload([
                'nama_toko' => 'Nama Yang Gagal',
                'maps_url' => 'bukan-url',
            ]))
            ->assertRedirect(route('admin.store'))
            ->assertSessionHasErrors('maps_url');

        $this->actingAs($this->admin)
            ->get(route('admin.store'))
            ->assertOk()
            ->assertSee('value="Nama Yang Gagal"', false);
    }
}

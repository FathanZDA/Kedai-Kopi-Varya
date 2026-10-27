<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function buatPengguna(UserRole $role, string $password = 'rahasia123'): User
    {
        return User::factory()->create([
            'name' => $role === UserRole::Admin ? 'Admin Varya' : 'Kasir Varya',
            'email' => $role === UserRole::Admin ? 'adminvarya@gmail.com' : 'kasirvarya@gmail.com',
            'password' => $password,
            'role' => $role,
        ]);
    }

    public function test_halaman_login_dapat_diakses_tamu(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Selamat datang')
            ->assertSee('Email')
            ->assertSee('Kata sandi')
            ->assertSee('Masuk ke akun');
    }

    public function test_admin_diarahkan_ke_dashboard_setelah_login(): void
    {
        $admin = $this->buatPengguna(UserRole::Admin);

        $response = $this->post('/login', [
            'email' => $admin->email,
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect('/admin/dashboard');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_kasir_diarahkan_ke_buat_pesanan_setelah_login(): void
    {
        $kasir = $this->buatPengguna(UserRole::Kasir);

        $response = $this->post('/login', [
            'email' => $kasir->email,
            'password' => 'rahasia123',
        ]);

        $response->assertRedirect('/kasir/pesanan/baru');
        $this->assertAuthenticatedAs($kasir);
    }

    public function test_kredensial_salah_menampilkan_pesan_dan_tidak_login(): void
    {
        $admin = $this->buatPengguna(UserRole::Admin);

        $this->from('/login')
            ->followingRedirects()
            ->post('/login', [
                'email' => $admin->email,
                'password' => 'kata-sandi-salah',
            ])
            ->assertOk()
            ->assertSee('Login gagal')
            ->assertSee('Email atau kata sandi yang Anda masukkan salah.');

        $this->assertGuest();
    }

    public function test_email_dan_password_kosong_menampilkan_pesan_validasi(): void
    {
        $this->from('/login')
            ->followingRedirects()
            ->post('/login', [])
            ->assertOk()
            ->assertSee('Login gagal')
            ->assertSee('Email wajib diisi.')
            ->assertSee('Kata sandi wajib diisi.');

        $this->assertGuest();
    }

    public function test_tamu_dialihkan_ke_login_saat_membuka_area_kasir_dan_admin(): void
    {
        foreach ([
            '/admin',
            '/admin/dashboard',
            '/admin/menu',
            '/kasir',
            '/kasir/pesanan/baru',
            '/kasir/pesanan',
            '/kasir/menu',
        ] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_kasir_tidak_bisa_mengakses_area_admin(): void
    {
        $kasir = $this->buatPengguna(UserRole::Kasir);

        $this->actingAs($kasir)->get('/admin/dashboard')->assertForbidden();
        $this->actingAs($kasir)->get('/admin/menu')->assertForbidden();
        $this->actingAs($kasir)->get('/admin/toko')->assertForbidden();
    }

    public function test_admin_tidak_bisa_mengakses_area_kasir(): void
    {
        $admin = $this->buatPengguna(UserRole::Admin);

        $this->actingAs($admin)->get('/kasir/pesanan/baru')->assertForbidden();
        $this->actingAs($admin)->get('/kasir/pesanan')->assertForbidden();
        $this->actingAs($admin)->get('/kasir/menu')->assertForbidden();
    }

    public function test_pengguna_yang_sudah_login_tidak_bisa_membuka_halaman_login(): void
    {
        $admin = $this->buatPengguna(UserRole::Admin);
        $kasir = User::factory()->create([
            'email' => 'kasirlain@gmail.com',
            'password' => 'rahasia123',
            'role' => UserRole::Kasir,
        ]);

        $this->actingAs($admin)->get('/login')->assertRedirect('/admin/dashboard');
        $this->actingAs($kasir)->get('/login')->assertRedirect('/kasir/pesanan/baru');
    }

    public function test_logout_mengakhiri_sesi(): void
    {
        $admin = $this->buatPengguna(UserRole::Admin);

        $this->actingAs($admin)
            ->post('/logout')
            ->assertRedirect('/login')
            ->assertSessionHas('status', 'Anda telah keluar dari akun.');

        $this->assertGuest();

        $this->get('/login')->assertSee('Anda telah keluar dari akun.');
    }

    public function test_password_disimpan_dalam_bentuk_hash(): void
    {
        $admin = $this->buatPengguna(UserRole::Admin, 'adminvarya123');

        $tersimpan = $admin->fresh()->getAttributes()['password'];

        $this->assertNotSame('adminvarya123', $tersimpan);
        $this->assertTrue(Hash::check('adminvarya123', $tersimpan));
    }

    public function test_akun_hasil_seeder_bisa_login_sesuai_role(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'adminvarya@gmail.com')->firstOrFail();
        $kasir = User::where('email', 'kasirvarya@gmail.com')->firstOrFail();

        $this->assertSame(UserRole::Admin, $admin->role);
        $this->assertSame(UserRole::Kasir, $kasir->role);
        $this->assertTrue(Hash::check('adminvarya123', $admin->getAttributes()['password']));
        $this->assertTrue(Hash::check('kasirvarya123', $kasir->getAttributes()['password']));

        $this->post('/login', [
            'email' => 'adminvarya@gmail.com',
            'password' => 'adminvarya123',
        ])->assertRedirect('/admin/dashboard');

        $this->post('/logout');

        $this->post('/login', [
            'email' => 'kasirvarya@gmail.com',
            'password' => 'kasirvarya123',
        ])->assertRedirect('/kasir/pesanan/baru');
    }

    public function test_percobaan_login_dibatasi_setelah_lima_kali_gagal(): void
    {
        $admin = $this->buatPengguna(UserRole::Admin);

        for ($i = 0; $i < 5; $i++) {
            $this->from('/login')->post('/login', [
                'email' => $admin->email,
                'password' => 'salah',
            ]);
        }

        $this->from('/login')
            ->followingRedirects()
            ->post('/login', [
                'email' => $admin->email,
                'password' => 'salah',
            ])
            ->assertOk()
            ->assertSee('Terlalu banyak percobaan masuk.');

        $this->assertGuest();
    }
}

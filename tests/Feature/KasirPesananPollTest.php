<?php

namespace Tests\Feature;

use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use App\Enums\StatusPesanan;
use App\Enums\SumberPesanan;
use App\Enums\TipePesanan;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class KasirPesananPollTest extends TestCase
{
    use RefreshDatabase;

    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-29 10:00:00');

        $this->kasir = User::factory()->create(['role' => UserRole::Kasir]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $ganti
     */
    private function buatOrder(array $ganti = []): Order
    {
        return Order::create(array_merge([
            'kode_pesanan' => 'AB12CD34',
            'nomor_antrean' => 1,
            'tanggal_antrean' => now()->toDateString(),
            'nama_pelanggan' => 'Anjani',
            'tipe_pesanan' => TipePesanan::DineIn,
            'sumber' => SumberPesanan::Kasir,
            'status_pesanan' => StatusPesanan::Antre,
            'metode_pembayaran' => MetodePembayaran::Tunai,
            'status_pembayaran' => StatusPembayaran::Lunas,
            'total' => 22000,
            'uang_diterima' => 25000,
            'kembalian' => 3000,
            'cashier_id' => $this->kasir->id,
            'waktu_lunas' => now(),
        ], $ganti));
    }

    public function test_poll_mengembalikan_snapshot_pesanan_hari_ini(): void
    {
        $this->buatOrder(['kode_pesanan' => 'AAAA1111']);
        $this->buatOrder([
            'kode_pesanan' => 'BBBB2222',
            'nomor_antrean' => 2,
            'status_pesanan' => StatusPesanan::Selesai,
        ]);

        $this->actingAs($this->kasir)
            ->getJson(route('kasir.pesanan.poll'))
            ->assertOk()
            ->assertJsonCount(2, 'data.pesanan')
            ->assertJsonPath('data.jumlah.antre', 1)
            ->assertJsonPath('data.jumlah.selesai', 1)
            ->assertJsonPath('data.jumlah.diproses', 0)
            ->assertJsonPath('data.jumlah.belum_dibayar', 0)
            ->assertJsonStructure([
                'data' => [
                    'waktu_server',
                    'jumlah' => ['belum_dibayar', 'antre', 'diproses', 'selesai'],
                    'pesanan' => [[
                        'id', 'code', 'queue', 'customer', 'type', 'type_label',
                        'source', 'status', 'payment', 'payment_label',
                        'payment_status', 'total', 'created_at', 'notes', 'items',
                    ]],
                ],
            ]);
    }

    public function test_waktu_server_memakai_zona_asia_jakarta(): void
    {
        $response = $this->actingAs($this->kasir)
            ->getJson(route('kasir.pesanan.poll'))
            ->assertOk();

        $this->assertMatchesRegularExpression(
            '/^2026-10-29T10:00:00\+07:00$/',
            $response->json('data.waktu_server'),
        );
    }

    public function test_poll_dengan_sejak_hanya_mengirim_pesanan_yang_berubah(): void
    {
        $this->buatOrder(['kode_pesanan' => 'LAMA1111']);
        $this->buatOrder(['kode_pesanan' => 'LAMA2222', 'nomor_antrean' => 2]);

        Carbon::setTestNow('2026-10-29 10:05:00');
        Order::query()->where('kode_pesanan', 'LAMA1111')->update(['status_pesanan' => StatusPesanan::Diproses]);

        $this->actingAs($this->kasir)
            ->getJson(route('kasir.pesanan.poll', ['sejak' => '2026-10-29T10:02:00+07:00']))
            ->assertOk()
            ->assertJsonCount(1, 'data.pesanan')
            ->assertJsonPath('data.pesanan.0.code', 'LAMA1111')
            ->assertJsonPath('data.pesanan.0.status', 'diproses');
    }

    public function test_sejak_tanpa_perubahan_mengembalikan_daftar_kosong_tapi_jumlah_tetap_penuh(): void
    {
        $this->buatOrder();

        $this->actingAs($this->kasir)
            ->getJson(route('kasir.pesanan.poll', ['sejak' => '2026-10-29T10:00:00+07:00']))
            ->assertOk()
            ->assertJsonCount(1, 'data.pesanan');

        $this->actingAs($this->kasir)
            ->getJson(route('kasir.pesanan.poll', ['sejak' => '2026-10-29T10:00:01+07:00']))
            ->assertOk()
            ->assertJsonCount(0, 'data.pesanan')
            ->assertJsonPath('data.jumlah.antre', 1);
    }

    public function test_pesanan_kemarin_tidak_ikut_di_polling(): void
    {
        $lama = $this->buatOrder(['kode_pesanan' => 'KEMARIN1']);
        $lama->forceFill(['created_at' => now()->subDay(), 'updated_at' => now()->subDay()])->saveQuietly();

        $this->actingAs($this->kasir)
            ->getJson(route('kasir.pesanan.poll'))
            ->assertOk()
            ->assertJsonCount(0, 'data.pesanan')
            ->assertJsonPath('data.jumlah.antre', 0);
    }

    public function test_parameter_sejak_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->kasir)
            ->getJson(route('kasir.pesanan.poll', ['sejak' => 'bukan-tanggal']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('sejak');
    }

    public function test_admin_tidak_boleh_memanggil_polling_kasir(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->getJson(route('kasir.pesanan.poll'))
            ->assertForbidden();
    }

    public function test_tamu_ditolak_saat_memanggil_polling(): void
    {
        $this->getJson(route('kasir.pesanan.poll'))->assertUnauthorized();
    }
}

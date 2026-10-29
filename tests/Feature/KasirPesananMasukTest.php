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
use Tests\TestCase;

class KasirPesananMasukTest extends TestCase
{
    use RefreshDatabase;

    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kasir = User::factory()->create(['role' => UserRole::Kasir]);
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

    public function test_pesanan_masuk_menampilkan_order_dari_database(): void
    {
        $this->buatOrder();

        $this->actingAs($this->kasir)
            ->get(route('kasir.pesanan.index'))
            ->assertOk()
            ->assertSee('Anjani')
            ->assertSee('AB12CD34')
            ->assertSee('001');
    }

    public function test_pesanan_kemarin_tidak_muncul_di_daftar_hari_ini(): void
    {
        $lama = $this->buatOrder([
            'kode_pesanan' => 'LAMA1234',
            'nama_pelanggan' => 'Pelanggan Kemarin',
        ]);

        $lama->forceFill(['created_at' => now()->subDay()])->saveQuietly();

        $this->actingAs($this->kasir)
            ->get(route('kasir.pesanan.index'))
            ->assertOk()
            ->assertDontSee('Pelanggan Kemarin');
    }

    public function test_kasir_dapat_mengubah_status_antre_menjadi_diproses(): void
    {
        $order = $this->buatOrder();

        $this->actingAs($this->kasir)
            ->patchJson(route('kasir.pesanan.status', $order), ['status' => 'diproses'])
            ->assertOk()
            ->assertJsonPath('data.status_pesanan', 'diproses')
            ->assertJsonPath('data.status_pesanan_label', 'Diproses');

        $this->assertSame(StatusPesanan::Diproses, $order->fresh()->status_pesanan);
    }

    public function test_kasir_dapat_menyelesaikan_pesanan_yang_sedang_diproses(): void
    {
        $order = $this->buatOrder(['status_pesanan' => StatusPesanan::Diproses]);

        $this->actingAs($this->kasir)
            ->patchJson(route('kasir.pesanan.status', $order), ['status' => 'selesai'])
            ->assertOk()
            ->assertJsonPath('data.status_pesanan', 'selesai');

        $this->assertSame(StatusPesanan::Selesai, $order->fresh()->status_pesanan);
    }

    public function test_transisi_melompati_tahap_ditolak(): void
    {
        $order = $this->buatOrder();

        $this->actingAs($this->kasir)
            ->patchJson(route('kasir.pesanan.status', $order), ['status' => 'selesai'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertSame(StatusPesanan::Antre, $order->fresh()->status_pesanan);
    }

    public function test_pesanan_selesai_tidak_bisa_diubah_lagi(): void
    {
        $order = $this->buatOrder(['status_pesanan' => StatusPesanan::Selesai]);

        $this->actingAs($this->kasir)
            ->patchJson(route('kasir.pesanan.status', $order), ['status' => 'diproses'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');

        $this->assertSame(StatusPesanan::Selesai, $order->fresh()->status_pesanan);
    }

    public function test_status_tidak_dikenal_ditolak(): void
    {
        $order = $this->buatOrder();

        $this->actingAs($this->kasir)
            ->patchJson(route('kasir.pesanan.status', $order), ['status' => 'dikirim'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_admin_tidak_boleh_mengubah_status_pesanan(): void
    {
        $order = $this->buatOrder();
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->patchJson(route('kasir.pesanan.status', $order), ['status' => 'diproses'])
            ->assertForbidden();

        $this->assertSame(StatusPesanan::Antre, $order->fresh()->status_pesanan);
    }

    public function test_tamu_tidak_boleh_mengubah_status_pesanan(): void
    {
        $order = $this->buatOrder();

        $this->patchJson(route('kasir.pesanan.status', $order), ['status' => 'diproses'])
            ->assertUnauthorized();

        $this->assertSame(StatusPesanan::Antre, $order->fresh()->status_pesanan);
    }

    public function test_order_qris_belum_dibayar_tampil_di_polling_dengan_id_midtrans(): void
    {
        $order = $this->buatOrder([
            'kode_pesanan' => 'QR12CD34',
            'nomor_antrean' => null,
            'tanggal_antrean' => null,
            'sumber' => SumberPesanan::Qr,
            'status_pesanan' => StatusPesanan::MenungguPembayaran,
            'metode_pembayaran' => MetodePembayaran::Qris,
            'status_pembayaran' => StatusPembayaran::Menunggu,
            'uang_diterima' => null,
            'kembalian' => null,
            'cashier_id' => null,
            'waktu_lunas' => null,
            'batas_waktu_pembayaran' => now()->addMinutes(15),
        ]);

        $order->payments()->create([
            'midtrans_order_id' => 'QR12CD34-P1',
            'status_transaksi' => 'pending',
            'status_code' => '201',
            'gross_amount' => '22000.00',
        ]);

        $this->actingAs($this->kasir)
            ->getJson(route('kasir.pesanan.poll'))
            ->assertOk()
            ->assertJsonPath('data.jumlah.belum_dibayar', 1)
            ->assertJsonPath('data.pesanan.0.code', 'QR12CD34')
            ->assertJsonPath('data.pesanan.0.queue', null)
            ->assertJsonPath('data.pesanan.0.midtrans_id', 'QR12CD34-P1');
    }
}

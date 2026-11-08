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

class KasirPesananDetailTest extends TestCase
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
            'nomor_antrean' => 3,
            'tanggal_antrean' => now()->toDateString(),
            'nama_pelanggan' => 'Anjani',
            'tipe_pesanan' => TipePesanan::DineIn,
            'catatan' => 'Tanpa es',
            'sumber' => SumberPesanan::Kasir,
            'status_pesanan' => StatusPesanan::Antre,
            'metode_pembayaran' => MetodePembayaran::Tunai,
            'status_pembayaran' => StatusPembayaran::Lunas,
            'total' => 44000,
            'uang_diterima' => 50000,
            'kembalian' => 6000,
            'cashier_id' => $this->kasir->id,
            'waktu_lunas' => now(),
        ], $ganti));
    }

    public function test_detail_mengembalikan_pesanan_hari_ini_dari_database(): void
    {
        $order = $this->buatOrder();

        $order->items()->create([
            'menu_id' => null,
            'nama_menu' => 'Varya Signature',
            'harga_satuan' => 22000,
            'jumlah' => 2,
            'subtotal' => 44000,
            'catatan' => 'Less sugar',
        ]);

        $this->actingAs($this->kasir)
            ->getJson(route('kasir.pesanan.show', $order))
            ->assertOk()
            ->assertJsonPath('data.id', (string) $order->id)
            ->assertJsonPath('data.code', 'AB12CD34')
            ->assertJsonPath('data.queue', '003')
            ->assertJsonPath('data.customer', 'Anjani')
            ->assertJsonPath('data.type', 'dine_in')
            ->assertJsonPath('data.notes', 'Tanpa es')
            ->assertJsonPath('data.status', 'antre')
            ->assertJsonPath('data.payment', 'tunai')
            ->assertJsonPath('data.payment_status', 'lunas')
            ->assertJsonPath('data.cash_received', 50000)
            ->assertJsonPath('data.change', 6000)
            ->assertJsonPath('data.total', 44000)
            ->assertJsonPath('data.items.0.name', 'Varya Signature')
            ->assertJsonPath('data.items.0.qty', 2)
            ->assertJsonPath('data.items.0.subtotal', 44000)
            ->assertJsonPath('data.items.0.note', 'Less sugar');
    }

    public function test_detail_qris_menampilkan_id_midtrans_terakhir(): void
    {
        $order = $this->buatOrder([
            'kode_pesanan' => 'QR34EF56',
            'nomor_antrean' => null,
            'tanggal_antrean' => null,
            'sumber' => SumberPesanan::Qr,
            'status_pesanan' => StatusPesanan::MenungguPembayaran,
            'status_pembayaran' => StatusPembayaran::Menunggu,
            'metode_pembayaran' => MetodePembayaran::Qris,
            'uang_diterima' => null,
            'kembalian' => null,
            'waktu_lunas' => null,
        ]);

        foreach (['QR34EF56-P1', 'QR34EF56-P2'] as $midtransOrderId) {
            $order->payments()->create([
                'midtrans_order_id' => $midtransOrderId,
                'status_transaksi' => 'pending',
                'status_code' => '201',
                'gross_amount' => 44000,
                'waktu_dibuat' => now(),
            ]);
        }

        $this->actingAs($this->kasir)
            ->getJson(route('kasir.pesanan.show', $order))
            ->assertOk()
            ->assertJsonPath('data.queue', null)
            ->assertJsonPath('data.payment', 'qris')
            ->assertJsonPath('data.payment_status', 'menunggu')
            ->assertJsonPath('data.midtrans_id', 'QR34EF56-P2');
    }

    public function test_pesanan_kemarin_tidak_bisa_dibuka(): void
    {
        $order = $this->buatOrder();

        $order->forceFill(['created_at' => now()->subDay(), 'updated_at' => now()->subDay()])->saveQuietly();

        $this->actingAs($this->kasir)
            ->getJson(route('kasir.pesanan.show', $order))
            ->assertNotFound();
    }

    public function test_pesanan_dibatalkan_tidak_bisa_dibuka(): void
    {
        $order = $this->buatOrder([
            'nomor_antrean' => null,
            'tanggal_antrean' => null,
            'status_pesanan' => StatusPesanan::Dibatalkan,
            'status_pembayaran' => StatusPembayaran::Dibatalkan,
            'waktu_lunas' => null,
        ]);

        $this->actingAs($this->kasir)
            ->getJson(route('kasir.pesanan.show', $order))
            ->assertNotFound();
    }

    public function test_order_tidak_dikenal_menghasilkan_404(): void
    {
        $this->actingAs($this->kasir)
            ->getJson(route('kasir.pesanan.show', 999999))
            ->assertNotFound();
    }

    public function test_detail_dibatasi_role_kasir(): void
    {
        $order = $this->buatOrder();

        $this->getJson(route('kasir.pesanan.show', $order))->assertUnauthorized();

        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->getJson(route('kasir.pesanan.show', $order))
            ->assertForbidden();
    }
}

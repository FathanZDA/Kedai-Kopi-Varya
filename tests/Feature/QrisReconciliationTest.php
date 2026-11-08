<?php

namespace Tests\Feature;

use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use App\Enums\StatusPesanan;
use App\Enums\SumberPesanan;
use App\Enums\TipePesanan;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class QrisReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private const API_URL = 'https://api.sandbox.midtrans.com/v2';

    private int $urutan = 0;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.midtrans.server_key' => 'SB-Mid-server-test',
            'services.midtrans.client_key' => 'SB-Mid-client-test',
            'services.midtrans.is_production' => false,
            'services.midtrans.api_sandbox_url' => self::API_URL,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 11, 1, 12, 0, 0, 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $ganti
     */
    private function buatOrder(array $ganti = [], ?Carbon $dibuat = null): Order
    {
        $this->urutan++;

        $order = Order::create(array_merge([
            'kode_pesanan' => 'QR'.str_pad((string) $this->urutan, 4, '0', STR_PAD_LEFT),
            'nama_pelanggan' => 'Anjani',
            'tipe_pesanan' => TipePesanan::DineIn,
            'sumber' => SumberPesanan::Qr,
            'status_pesanan' => StatusPesanan::MenungguPembayaran,
            'metode_pembayaran' => MetodePembayaran::Qris,
            'status_pembayaran' => StatusPembayaran::Menunggu,
            'total' => 22000,
            'batas_waktu_pembayaran' => now()->addMinutes(15),
        ], $ganti));

        if ($dibuat) {
            $order->forceFill([
                'created_at' => $dibuat,
                'updated_at' => $dibuat,
            ])->saveQuietly();
        }

        $order->items()->create([
            'menu_id' => null,
            'nama_menu' => 'Kopi Susu',
            'harga_satuan' => 22000,
            'jumlah' => 1,
            'subtotal' => 22000,
            'catatan' => null,
        ]);

        return $order;
    }

    private function tambahPembayaran(
        Order $order,
        string $status = 'pending',
        ?Carbon $dibuat = null,
        int $nomor = 1,
    ): Payment {
        $payment = $order->payments()->create([
            'midtrans_order_id' => $order->kode_pesanan.'-P'.$nomor,
            'status_transaksi' => $status,
            'status_code' => $status === 'pending' ? '201' : '200',
            'gross_amount' => number_format($order->total, 2, '.', ''),
            'snap_token' => 'snap-token-1',
            'snap_redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/1',
            'waktu_dibuat' => $dibuat ?? now(),
        ]);

        if ($dibuat) {
            $payment->forceFill([
                'created_at' => $dibuat,
                'updated_at' => $dibuat,
            ])->saveQuietly();
        }

        return $payment;
    }

    /**
     * @return array<string, mixed>
     */
    private function responsMidtrans(Payment $payment, string $status): array
    {
        return [
            'status_code' => '200',
            'status_message' => 'Success',
            'transaction_id' => 'tx-'.$payment->id,
            'order_id' => $payment->midtrans_order_id,
            'gross_amount' => $payment->gross_amount,
            'payment_type' => 'qris',
            'transaction_status' => $status,
            'fraud_status' => 'accept',
        ];
    }

    public function test_reconcile_menerapkan_settlement_dan_memberi_nomor_antrean(): void
    {
        $order = $this->buatOrder(dibuat: now()->subMinutes(5));
        $payment = $this->tambahPembayaran($order, dibuat: now()->subMinutes(5));

        Http::fake([
            self::API_URL.'/*' => Http::response($this->responsMidtrans($payment, 'settlement')),
        ]);

        $this->artisan('qris:reconcile')
            ->expectsOutputToContain('1 diperiksa, 0 ditutup, 0 gagal')
            ->assertSuccessful();

        $order->refresh();

        $this->assertSame(StatusPesanan::Antre, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Lunas, $order->status_pembayaran);
        $this->assertNotNull($order->nomor_antrean);
        $this->assertSame('settlement', $payment->refresh()->status_transaksi);
        $this->assertDatabaseCount('payment_notifications', 0);
    }

    public function test_reconcile_menerapkan_status_407_expire_tanpa_nomor_antrean(): void
    {
        $order = $this->buatOrder(dibuat: now()->subMinutes(5));
        $payment = $this->tambahPembayaran($order, dibuat: now()->subMinutes(5));

        Http::fake([
            self::API_URL.'/*' => Http::response(array_merge(
                $this->responsMidtrans($payment, 'expire'),
                ['status_code' => '407'],
            )),
        ]);

        $this->artisan('qris:reconcile')
            ->expectsOutputToContain('1 diperiksa, 0 ditutup, 0 gagal')
            ->assertSuccessful();

        $order->refresh();

        $this->assertSame(StatusPesanan::MenungguPembayaran, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Kedaluwarsa, $order->status_pembayaran);
        $this->assertSame('expire', $payment->refresh()->status_transaksi);
        $this->assertNull($order->nomor_antrean);
    }

    public function test_reconcile_menutup_pesanan_lebih_dari_24_jam_tanpa_antrean(): void
    {
        $order = $this->buatOrder(dibuat: now()->subHours(25));
        $payment = $this->tambahPembayaran($order, 'failure', now()->subHours(25));
        $order->update(['status_pembayaran' => StatusPembayaran::Gagal]);

        Http::fake();

        $this->artisan('qris:reconcile')
            ->expectsOutputToContain('0 diperiksa, 1 ditutup, 0 gagal')
            ->assertSuccessful();

        $order->refresh();

        $this->assertSame(StatusPesanan::Dibatalkan, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Dibatalkan, $order->status_pembayaran);
        $this->assertNull($order->nomor_antrean);
        $this->assertSame('failure', $payment->refresh()->status_transaksi);
        Http::assertNothingSent();
    }

    public function test_reconcile_menutup_pending_lama_setelah_status_midtrans_tetap_pending(): void
    {
        $order = $this->buatOrder(dibuat: now()->subHours(25));
        $payment = $this->tambahPembayaran($order, dibuat: now()->subHours(25));

        Http::fake([
            self::API_URL.'/*' => Http::response($this->responsMidtrans($payment, 'pending')),
        ]);

        $this->artisan('qris:reconcile')
            ->expectsOutputToContain('1 diperiksa, 1 ditutup, 0 gagal')
            ->assertSuccessful();

        $order->refresh();

        $this->assertSame(StatusPesanan::Dibatalkan, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Dibatalkan, $order->status_pembayaran);
        $this->assertNull($order->nomor_antrean);
    }

    public function test_reconcile_tidak_menutup_order_lama_yang_punya_bukti_settlement_lokal(): void
    {
        $order = $this->buatOrder(dibuat: now()->subHours(25));
        $this->tambahPembayaran($order, 'settlement', now()->subHours(25));

        Http::fake();

        $this->artisan('qris:reconcile')
            ->expectsOutputToContain('0 diperiksa, 0 ditutup, 0 gagal')
            ->assertSuccessful();

        $order->refresh();

        $this->assertSame(StatusPesanan::MenungguPembayaran, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Menunggu, $order->status_pembayaran);
        Http::assertNothingSent();
    }

    public function test_reconcile_tidak_menutup_order_lama_yang_masih_punya_percobaan_aktif(): void
    {
        $order = $this->buatOrder(dibuat: now()->subHours(25));
        $this->tambahPembayaran($order, 'kedaluwarsa', now()->subHours(25));
        $order->update(['status_pembayaran' => StatusPembayaran::Kedaluwarsa]);
        $this->tambahPembayaran($order, 'pending', now()->subMinute(), 2);

        Http::fake();

        $this->artisan('qris:reconcile')
            ->expectsOutputToContain('0 diperiksa, 0 ditutup, 0 gagal')
            ->assertSuccessful();

        $order->refresh();

        $this->assertSame(StatusPesanan::MenungguPembayaran, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Kedaluwarsa, $order->status_pembayaran);
        Http::assertNothingSent();
    }

    public function test_reconcile_melewati_pembayaran_yang_baru_berumur_kurang_dari_dua_menit(): void
    {
        $order = $this->buatOrder(dibuat: now()->subMinute());
        $this->tambahPembayaran($order, dibuat: now()->subMinute());

        Http::fake();

        $this->artisan('qris:reconcile')
            ->expectsOutputToContain('0 diperiksa, 0 ditutup, 0 gagal')
            ->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(StatusPesanan::MenungguPembayaran, $order->refresh()->status_pesanan);
    }

    public function test_reconcile_mencatat_kegagalan_midtrans_tanpa_mengubah_order(): void
    {
        $order = $this->buatOrder(dibuat: now()->subMinutes(5));
        $this->tambahPembayaran($order, dibuat: now()->subMinutes(5));

        Http::fake([
            self::API_URL.'/*' => Http::response([
                'status_code' => '500',
                'status_message' => 'Sorry. Our system is recovering from unexpected issues. Please retry.',
            ], 500),
        ]);

        $this->artisan('qris:reconcile')
            ->expectsOutputToContain('1 diperiksa, 0 ditutup, 1 gagal')
            ->assertSuccessful();

        $order->refresh();

        $this->assertSame(StatusPesanan::MenungguPembayaran, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Menunggu, $order->status_pembayaran);
    }

    public function test_reconcile_idempoten_saat_dijalankan_dua_kali(): void
    {
        $order = $this->buatOrder(dibuat: now()->subHours(25));
        $this->tambahPembayaran($order, 'failure', now()->subHours(25));
        $order->update(['status_pembayaran' => StatusPembayaran::Gagal]);

        Http::fake();

        $this->artisan('qris:reconcile')
            ->expectsOutputToContain('0 diperiksa, 1 ditutup, 0 gagal')
            ->assertSuccessful();

        $this->artisan('qris:reconcile')
            ->expectsOutputToContain('0 diperiksa, 0 ditutup, 0 gagal')
            ->assertSuccessful();

        $this->assertSame(StatusPesanan::Dibatalkan, $order->refresh()->status_pesanan);
        $this->assertDatabaseCount('queue_counters', 0);
    }
}

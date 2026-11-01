<?php

namespace Tests\Feature;

use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use App\Enums\StatusPesanan;
use App\Enums\SumberPesanan;
use App\Enums\TipePesanan;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class KasirQrisOpsTest extends TestCase
{
    use RefreshDatabase;

    private const API_URL = 'https://api.sandbox.midtrans.com/v2';

    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.midtrans.server_key' => 'SB-Mid-server-test',
            'services.midtrans.client_key' => 'SB-Mid-client-test',
            'services.midtrans.is_production' => false,
            'services.midtrans.api_sandbox_url' => self::API_URL,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 10, 31, 14, 0, 0, 'Asia/Jakarta'));

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
        $order = Order::create(array_merge([
            'kode_pesanan' => 'QR12CD34',
            'nama_pelanggan' => 'Anjani',
            'tipe_pesanan' => TipePesanan::DineIn,
            'sumber' => SumberPesanan::Qr,
            'status_pesanan' => StatusPesanan::MenungguPembayaran,
            'metode_pembayaran' => MetodePembayaran::Qris,
            'status_pembayaran' => StatusPembayaran::Menunggu,
            'total' => 22000,
            'batas_waktu_pembayaran' => now()->addMinutes(15),
        ], $ganti));

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
        int $nomor = 1,
        string $status = 'pending',
        ?string $token = 'snap-token-1',
    ): Payment {
        return $order->payments()->create([
            'midtrans_order_id' => $order->kode_pesanan.'-P'.$nomor,
            'status_transaksi' => $status,
            'status_code' => $status === 'pending' ? '201' : '200',
            'gross_amount' => number_format($order->total, 2, '.', ''),
            'snap_token' => $token,
            'snap_redirect_url' => $token
                ? 'https://app.sandbox.midtrans.com/snap/v4/redirection/'.$nomor
                : null,
            'waktu_dibuat' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function responsMidtrans(
        Payment $payment,
        string $status,
        ?int $httpStatus = 200,
    ): array {
        return [
            'status_code' => (string) ($httpStatus ?? 200),
            'status_message' => 'Success',
            'transaction_id' => 'tx-'.$payment->id,
            'order_id' => $payment->midtrans_order_id,
            'gross_amount' => $payment->gross_amount,
            'payment_type' => 'qris',
            'transaction_status' => $status,
            'fraud_status' => 'accept',
        ];
    }

    public function test_cancel_memanggil_midtrans_dan_menyimpan_pembatalan_tanpa_nomor_antrean(): void
    {
        $order = $this->buatOrder();
        $payment = $this->tambahPembayaran($order);

        Http::fake([
            self::API_URL.'/*' => Http::response($this->responsMidtrans($payment, 'cancel')),
        ]);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.cancel', ['order' => $order->id]))
            ->assertOk()
            ->assertJsonPath('data.status', 'dibatalkan')
            ->assertJsonPath('data.payment_status', 'dibatalkan')
            ->assertJsonPath('data.queue', null);

        $order->refresh();
        $payment->refresh();

        $this->assertSame(StatusPesanan::Dibatalkan, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Dibatalkan, $order->status_pembayaran);
        $this->assertNull($order->nomor_antrean);
        $this->assertSame('cancel', $payment->status_transaksi);
        $this->assertSame('200', $payment->status_code);
        $this->assertDatabaseCount('payment_notifications', 0);

        $this->getJson(route('kasir.pesanan.poll'))
            ->assertOk()
            ->assertJsonPath('data.jumlah.belum_dibayar', 0)
            ->assertJsonCount(0, 'data.pesanan');

        Http::assertSent(function (Request $request) use ($payment): bool {
            return $request->method() === 'POST'
                && $request->url() === self::API_URL.'/'.$payment->midtrans_order_id.'/cancel';
        });
    }

    public function test_cancel_memakai_status_expire_bila_midtrans_menolak_pembatalan(): void
    {
        $order = $this->buatOrder();
        $payment = $this->tambahPembayaran($order);

        Http::fake([
            self::API_URL.'/'.$payment->midtrans_order_id.'/cancel' => Http::response([
                'status_code' => '412',
                'status_message' => 'Transaction already expired.',
            ], 412),
            self::API_URL.'/'.$payment->midtrans_order_id.'/status' => Http::response(
                $this->responsMidtrans($payment, 'expire'),
            ),
        ]);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.cancel', ['order' => $order->id]))
            ->assertOk()
            ->assertJsonPath('data.status', 'dibatalkan')
            ->assertJsonPath('data.payment_status', 'dibatalkan')
            ->assertJsonPath('data.queue', null);

        $order->refresh();
        $payment->refresh();

        $this->assertSame(StatusPesanan::Dibatalkan, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Dibatalkan, $order->status_pembayaran);
        $this->assertNull($order->nomor_antrean);
        $this->assertSame('expire', $payment->status_transaksi);
    }

    public function test_cancel_memakai_status_407_expire_bila_midtrans_menolak_pembatalan(): void
    {
        $order = $this->buatOrder();
        $payment = $this->tambahPembayaran($order);

        $expire = array_merge($this->responsMidtrans($payment, 'expire'), [
            'status_code' => '407',
            'status_message' => 'Success, transaction is found',
        ]);

        Http::fake([
            self::API_URL.'/'.$payment->midtrans_order_id.'/cancel' => Http::response([
                'status_code' => '412',
                'status_message' => 'Transaction already expired.',
            ], 412),
            self::API_URL.'/'.$payment->midtrans_order_id.'/status' => Http::response($expire),
        ]);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.cancel', ['order' => $order->id]))
            ->assertOk()
            ->assertJsonPath('data.status', 'dibatalkan')
            ->assertJsonPath('data.payment_status', 'dibatalkan')
            ->assertJsonPath('data.queue', null);

        $order->refresh();
        $payment->refresh();

        $this->assertSame(StatusPesanan::Dibatalkan, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Dibatalkan, $order->status_pembayaran);
        $this->assertNull($order->nomor_antrean);
        $this->assertSame('expire', $payment->status_transaksi);
        $this->assertSame('407', $payment->status_code);
    }

    public function test_cancel_ditolak_bila_status_midtrans_ternyata_settlement(): void
    {
        $order = $this->buatOrder();
        $payment = $this->tambahPembayaran($order);

        Http::fake([
            self::API_URL.'/'.$payment->midtrans_order_id.'/cancel' => Http::response([
                'status_code' => '412',
                'status_message' => 'Transaction has been paid.',
            ], 412),
            self::API_URL.'/'.$payment->midtrans_order_id.'/status' => Http::response(
                $this->responsMidtrans($payment, 'settlement'),
            ),
        ]);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.cancel', ['order' => $order->id]))
            ->assertStatus(409);

        $order->refresh();
        $payment->refresh();

        $this->assertSame(StatusPesanan::Antre, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Lunas, $order->status_pembayaran);
        $this->assertNotNull($order->nomor_antrean);
        $this->assertSame('settlement', $payment->status_transaksi);
    }

    public function test_cancel_tetap_menunggu_bila_status_midtrans_masih_pending(): void
    {
        $order = $this->buatOrder();
        $payment = $this->tambahPembayaran($order);

        Http::fake([
            self::API_URL.'/'.$payment->midtrans_order_id.'/cancel' => Http::response([
                'status_code' => '412',
                'status_message' => 'Transaction cannot be canceled.',
            ], 412),
            self::API_URL.'/'.$payment->midtrans_order_id.'/status' => Http::response(
                $this->responsMidtrans($payment, 'pending'),
            ),
        ]);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.cancel', ['order' => $order->id]))
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Midtrans belum dapat membatalkan transaksi. Pesanan tetap menunggu dan akan ditutup otomatis setelah 24 jam.',
            );

        $order->refresh();
        $payment->refresh();

        $this->assertSame(StatusPesanan::MenungguPembayaran, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Menunggu, $order->status_pembayaran);
        $this->assertNull($order->nomor_antrean);
        $this->assertSame('pending', $payment->status_transaksi);
    }

    public function test_cancel_mengulang_pembatalan_setelah_midtrans_5xx_lalu_berhasil(): void
    {
        $order = $this->buatOrder();
        $payment = $this->tambahPembayaran($order);

        Http::fake([
            self::API_URL.'/'.$payment->midtrans_order_id.'/cancel' => Http::sequence()
                ->push([
                    'status_code' => '500',
                    'status_message' => 'Sorry. Our system is recovering from unexpected issues. Please retry.',
                ], 500)
                ->push($this->responsMidtrans($payment, 'cancel'), 200),
        ]);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.cancel', ['order' => $order->id]))
            ->assertOk()
            ->assertJsonPath('data.status', 'dibatalkan')
            ->assertJsonPath('data.queue', null);

        $this->assertSame(StatusPesanan::Dibatalkan, $order->refresh()->status_pesanan);
        $this->assertSame('cancel', $payment->refresh()->status_transaksi);
        Http::assertSentCount(2);
    }

    public function test_cancel_5xx_berulang_dan_status_pending_tetap_menunggu(): void
    {
        $order = $this->buatOrder();
        $payment = $this->tambahPembayaran($order);

        Http::fake([
            self::API_URL.'/'.$payment->midtrans_order_id.'/cancel' => Http::sequence()
                ->push(['status_code' => '500', 'status_message' => 'Sedang pulih.'], 500)
                ->push(['status_code' => '500', 'status_message' => 'Sedang pulih.'], 500),
            self::API_URL.'/'.$payment->midtrans_order_id.'/status' => Http::response(
                $this->responsMidtrans($payment, 'pending'),
            ),
        ]);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.cancel', ['order' => $order->id]))
            ->assertStatus(409)
            ->assertJsonPath(
                'message',
                'Midtrans belum dapat membatalkan transaksi. Pesanan tetap menunggu dan akan ditutup otomatis setelah 24 jam.',
            );

        $this->assertSame(StatusPesanan::MenungguPembayaran, $order->refresh()->status_pesanan);
        $this->assertSame('pending', $payment->refresh()->status_transaksi);
        Http::assertSentCount(3);
    }

    public function test_cancel_tidak_membatalkan_lokal_bila_respons_cancel_masih_pending(): void
    {
        $order = $this->buatOrder();
        $payment = $this->tambahPembayaran($order);

        Http::fake([
            self::API_URL.'/*' => Http::response($this->responsMidtrans($payment, 'pending')),
        ]);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.cancel', ['order' => $order->id]))
            ->assertStatus(409);

        $order->refresh();
        $payment->refresh();

        $this->assertSame(StatusPesanan::MenungguPembayaran, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Menunggu, $order->status_pembayaran);
        $this->assertSame('pending', $payment->status_transaksi);
        $this->assertNull($order->nomor_antrean);
    }

    public function test_cancel_tetap_menunggu_bila_midtrans_tidak_dapat_dihubungi(): void
    {
        $order = $this->buatOrder();
        $this->tambahPembayaran($order);

        Http::fake([
            self::API_URL.'/*' => Http::failedConnection(),
        ]);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.cancel', ['order' => $order->id]))
            ->assertStatus(502);

        $order->refresh();

        $this->assertSame(StatusPesanan::MenungguPembayaran, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Menunggu, $order->status_pembayaran);
        $this->assertNull($order->nomor_antrean);
    }

    public function test_cancel_pesanan_lunas_ditolak_tanpa_memanggil_midtrans(): void
    {
        $order = $this->buatOrder([
            'status_pesanan' => StatusPesanan::Antre,
            'status_pembayaran' => StatusPembayaran::Lunas,
            'nomor_antrean' => 4,
            'tanggal_antrean' => now()->toDateString(),
            'waktu_lunas' => now(),
            'batas_waktu_pembayaran' => null,
        ]);
        $this->tambahPembayaran($order, 1, 'settlement');

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.cancel', ['order' => $order->id]))
            ->assertStatus(409);

        $this->assertSame(StatusPesanan::Antre, $order->refresh()->status_pesanan);
        Http::assertNothingSent();
    }

    public function test_cancel_percobaan_lokal_yang_gagal_tidak_memanggil_midtrans(): void
    {
        $order = $this->buatOrder([
            'status_pembayaran' => StatusPembayaran::Gagal,
        ]);
        $payment = $this->tambahPembayaran($order, 1, 'failure', null);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.cancel', ['order' => $order->id]))
            ->assertOk()
            ->assertJsonPath('data.status', 'dibatalkan');

        $this->assertSame(StatusPesanan::Dibatalkan, $order->refresh()->status_pesanan);
        $this->assertSame('failure', $payment->refresh()->status_transaksi);
        Http::assertNothingSent();
    }

    public function test_cancel_idempoten_untuk_pesanan_yang_sudah_dibatalkan(): void
    {
        $order = $this->buatOrder([
            'status_pesanan' => StatusPesanan::Dibatalkan,
            'status_pembayaran' => StatusPembayaran::Dibatalkan,
            'batas_waktu_pembayaran' => null,
        ]);
        $this->tambahPembayaran($order, 1, 'cancel', null);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.cancel', ['order' => $order->id]))
            ->assertOk()
            ->assertJsonPath('data.status', 'dibatalkan');

        Http::assertNothingSent();
    }

    public function test_cancel_menjawab_503_bila_kredensial_midtrans_kosong(): void
    {
        config(['services.midtrans.server_key' => null]);

        $order = $this->buatOrder();
        $this->tambahPembayaran($order);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.cancel', ['order' => $order->id]))
            ->assertStatus(503);

        $this->assertSame(StatusPesanan::MenungguPembayaran, $order->refresh()->status_pesanan);
        Http::assertNothingSent();
    }

    public function test_check_status_settlement_memberi_nomor_antrean_dan_idempoten(): void
    {
        $order = $this->buatOrder();
        $payment = $this->tambahPembayaran($order);

        Http::fake([
            self::API_URL.'/*' => Http::response($this->responsMidtrans($payment, 'settlement')),
        ]);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.check-status', ['order' => $order->id]))
            ->assertOk()
            ->assertJsonPath('data.status', 'antre')
            ->assertJsonPath('data.payment_status', 'lunas')
            ->assertJsonPath('data.queue', '001');

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.check-status', ['order' => $order->id]))
            ->assertOk()
            ->assertJsonPath('data.queue', '001');

        $order->refresh();

        $this->assertSame(1, $order->nomor_antrean);
        $this->assertDatabaseCount('queue_counters', 1);
        $this->assertDatabaseCount('payment_notifications', 0);

        Http::assertSent(function (Request $request) use ($payment): bool {
            return $request->method() === 'GET'
                && $request->url() === self::API_URL.'/'.$payment->midtrans_order_id.'/status';
        });
    }

    public function test_check_status_pending_tidak_memberi_nomor_antrean(): void
    {
        $order = $this->buatOrder();
        $payment = $this->tambahPembayaran($order);

        Http::fake([
            self::API_URL.'/*' => Http::response($this->responsMidtrans($payment, 'pending')),
        ]);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.check-status', ['order' => $order->id]))
            ->assertOk()
            ->assertJsonPath('data.status', 'menunggu_pembayaran')
            ->assertJsonPath('data.payment_status', 'menunggu')
            ->assertJsonPath('data.queue', null);

        $this->assertSame(StatusPesanan::MenungguPembayaran, $order->refresh()->status_pesanan);
    }

    public function test_check_status_407_expire_menandai_kedaluwarsa_tanpa_nomor_antrean(): void
    {
        $order = $this->buatOrder();
        $payment = $this->tambahPembayaran($order);

        $expire = array_merge($this->responsMidtrans($payment, 'expire'), [
            'status_code' => '407',
            'status_message' => 'Success, transaction is found',
        ]);

        Http::fake([
            self::API_URL.'/*' => Http::response($expire),
        ]);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.check-status', ['order' => $order->id]))
            ->assertOk()
            ->assertJsonPath('data.status', 'menunggu_pembayaran')
            ->assertJsonPath('data.payment_status', 'kedaluwarsa')
            ->assertJsonPath('data.queue', null);

        $order->refresh();
        $payment->refresh();

        $this->assertSame(StatusPesanan::MenungguPembayaran, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Kedaluwarsa, $order->status_pembayaran);
        $this->assertSame('expire', $payment->status_transaksi);
        $this->assertSame('407', $payment->status_code);
        $this->assertNull($order->nomor_antrean);
    }

    public function test_check_status_tanpa_gross_amount_tidak_menandai_lunas(): void
    {
        $order = $this->buatOrder();
        $payment = $this->tambahPembayaran($order);

        $payload = $this->responsMidtrans($payment, 'settlement');
        unset($payload['gross_amount']);

        Http::fake([
            self::API_URL.'/*' => Http::response($payload),
        ]);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.check-status', ['order' => $order->id]))
            ->assertOk()
            ->assertJsonPath('data.status', 'menunggu_pembayaran')
            ->assertJsonPath('data.payment_status', 'perlu_diperiksa')
            ->assertJsonPath('data.queue', null);

        $order->refresh();

        $this->assertSame(StatusPembayaran::PerluDiperiksa, $order->status_pembayaran);
        $this->assertNull($order->nomor_antrean);
        $this->assertDatabaseCount('queue_counters', 0);
    }

    public function test_check_status_menolak_respons_midtrans_untuk_order_lain(): void
    {
        $order = $this->buatOrder();
        $payment = $this->tambahPembayaran($order);

        $payload = $this->responsMidtrans($payment, 'settlement');
        $payload['order_id'] = 'ORDER-LAIN-P1';

        Http::fake([
            self::API_URL.'/*' => Http::response($payload),
        ]);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.check-status', ['order' => $order->id]))
            ->assertStatus(502);

        $order->refresh();
        $payment->refresh();

        $this->assertSame(StatusPesanan::MenungguPembayaran, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Menunggu, $order->status_pembayaran);
        $this->assertSame('pending', $payment->status_transaksi);
        $this->assertNull($order->nomor_antrean);
        $this->assertDatabaseCount('queue_counters', 0);
    }

    public function test_settlement_setelah_pembatalan_lokal_menjadi_perlu_diperiksa_tanpa_nomor_antrean(): void
    {
        $order = $this->buatOrder([
            'status_pesanan' => StatusPesanan::Dibatalkan,
            'batas_waktu_pembayaran' => null,
        ]);
        $payment = $this->tambahPembayaran($order);

        Http::fake([
            self::API_URL.'/*' => Http::response($this->responsMidtrans($payment, 'settlement')),
        ]);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.check-status', ['order' => $order->id]))
            ->assertOk()
            ->assertJsonPath('data.status', 'dibatalkan')
            ->assertJsonPath('data.payment_status', 'perlu_diperiksa')
            ->assertJsonPath('data.queue', null);

        $order->refresh();

        $this->assertSame(StatusPembayaran::PerluDiperiksa, $order->status_pembayaran);
        $this->assertNull($order->nomor_antrean);
    }

    public function test_check_status_hanya_untuk_pesanan_qris(): void
    {
        $order = $this->buatOrder([
            'kode_pesanan' => 'KASIR123',
            'sumber' => SumberPesanan::Kasir,
            'status_pesanan' => StatusPesanan::Antre,
            'metode_pembayaran' => MetodePembayaran::Tunai,
            'status_pembayaran' => StatusPembayaran::Lunas,
            'nomor_antrean' => 2,
            'tanggal_antrean' => now()->toDateString(),
            'waktu_lunas' => now(),
            'batas_waktu_pembayaran' => null,
        ]);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.check-status', ['order' => $order->id]))
            ->assertStatus(409);

        Http::assertNothingSent();
    }

    public function test_check_status_menjawab_503_bila_kredensial_midtrans_kosong(): void
    {
        config(['services.midtrans.server_key' => null]);

        $order = $this->buatOrder();
        $this->tambahPembayaran($order);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.check-status', ['order' => $order->id]))
            ->assertStatus(503);

        Http::assertNothingSent();
    }

    public function test_admin_tidak_dapat_membatalkan_atau_memeriksa_status(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $order = $this->buatOrder();
        $this->tambahPembayaran($order);

        $this->actingAs($admin)
            ->postJson(route('kasir.pesanan.cancel', ['order' => $order->id]))
            ->assertForbidden();

        $this->actingAs($admin)
            ->postJson(route('kasir.pesanan.check-status', ['order' => $order->id]))
            ->assertForbidden();

        Http::assertNothingSent();
    }
}

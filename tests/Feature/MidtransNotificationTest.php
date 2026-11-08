<?php

namespace Tests\Feature;

use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use App\Enums\StatusPemrosesanNotifikasi;
use App\Enums\StatusPesanan;
use App\Enums\SumberPesanan;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentNotification;
use App\Models\QueueCounter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class MidtransNotificationTest extends TestCase
{
    use RefreshDatabase;

    private const SERVER_KEY = 'SB-Mid-server-test';

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.midtrans.server_key' => self::SERVER_KEY]);

        Carbon::setTestNow(Carbon::create(2026, 10, 30, 10, 0, 0, 'Asia/Jakarta'));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * Order QRIS dengan satu percobaan pembayaran `-P1`.
     */
    private function orderQris(string $kode = 'V7K2M9QX', int $total = 45000): Order
    {
        $order = Order::create([
            'kode_pesanan' => $kode,
            'nama_pelanggan' => 'Budi',
            'tipe_pesanan' => 'dine_in',
            'sumber' => SumberPesanan::Qr,
            'status_pesanan' => StatusPesanan::MenungguPembayaran,
            'metode_pembayaran' => MetodePembayaran::Qris,
            'status_pembayaran' => StatusPembayaran::Menunggu,
            'total' => $total,
            'batas_waktu_pembayaran' => now()->addMinutes(15),
        ]);

        $this->tambahPercobaan($order, 1, $total);

        return $order;
    }

    private function tambahPercobaan(Order $order, int $nomor, int $total = 45000): Payment
    {
        return $order->payments()->create([
            'midtrans_order_id' => $order->kode_pesanan.'-P'.$nomor,
            'status_transaksi' => 'pending',
            'status_code' => '201',
            'gross_amount' => number_format($total, 2, '.', ''),
            'waktu_dibuat' => now(),
        ]);
    }

    /**
     * Payload notifikasi Midtrans lengkap dengan signature yang sah.
     *
     * @param  array<string, mixed>  $ganti
     * @return array<string, mixed>
     */
    private function payload(array $ganti = [], string $serverKey = self::SERVER_KEY): array
    {
        $payload = array_merge([
            'transaction_time' => '2026-10-30 10:01:00',
            'transaction_status' => 'settlement',
            'transaction_id' => 'abc-123',
            'status_message' => 'midtrans payment notification',
            'status_code' => '200',
            'payment_type' => 'other_qris',
            'order_id' => 'V7K2M9QX-P1',
            'gross_amount' => '45000.00',
            'fraud_status' => 'accept',
            'currency' => 'IDR',
        ], $ganti);

        $payload['signature_key'] = hash('sha512',
            $payload['order_id'].
            $payload['status_code'].
            $payload['gross_amount'].
            $serverKey
        );

        return $payload;
    }

    public function test_settlement_menandai_lunas_dan_memberi_nomor_antrean(): void
    {
        $order = $this->orderQris();

        $respons = $this->postJson(route('midtrans.notification'), $this->payload());

        $respons->assertOk()->assertJson([
            'message' => 'Notifikasi diproses.',
            'status_pemrosesan' => StatusPemrosesanNotifikasi::Diproses->value,
        ]);

        $order->refresh();

        $this->assertSame(StatusPembayaran::Lunas, $order->status_pembayaran);
        $this->assertSame(StatusPesanan::Antre, $order->status_pesanan);
        $this->assertSame(1, $order->nomor_antrean);
        $this->assertSame(now()->toDateString(), $order->tanggal_antrean->toDateString());
        $this->assertNotNull($order->waktu_lunas);
    }

    public function test_capture_dengan_fraud_accept_menandai_lunas(): void
    {
        $order = $this->orderQris();

        $this->postJson(route('midtrans.notification'), $this->payload([
            'transaction_status' => 'capture',
            'fraud_status' => 'accept',
        ]))->assertOk();

        $order->refresh();

        $this->assertSame(StatusPembayaran::Lunas, $order->status_pembayaran);
        $this->assertSame(1, $order->nomor_antrean);
    }

    public function test_capture_dengan_fraud_challenge_ditandai_perlu_diperiksa(): void
    {
        $order = $this->orderQris();

        $this->postJson(route('midtrans.notification'), $this->payload([
            'transaction_status' => 'capture',
            'fraud_status' => 'challenge',
        ]))->assertOk();

        $order->refresh();

        $this->assertSame(StatusPembayaran::PerluDiperiksa, $order->status_pembayaran);
        $this->assertSame(StatusPesanan::MenungguPembayaran, $order->status_pesanan);
        $this->assertNull($order->nomor_antrean);
    }

    public function test_notifikasi_ganda_tidak_memberi_nomor_antrean_ganda(): void
    {
        $order = $this->orderQris();

        $this->postJson(route('midtrans.notification'), $this->payload())->assertOk();
        $this->postJson(route('midtrans.notification'), $this->payload())->assertOk();
        $this->postJson(route('midtrans.notification'), $this->payload())->assertOk();

        $order->refresh();

        $this->assertSame(StatusPembayaran::Lunas, $order->status_pembayaran);
        $this->assertSame(1, $order->nomor_antrean);
        $this->assertSame(1, QueueCounter::query()->firstOrFail()->nomor_terakhir);
        $this->assertSame(3, PaymentNotification::query()->count());
    }

    public function test_pembayaran_kedua_pada_order_lunas_ditandai_perlu_diperiksa(): void
    {
        $order = $this->orderQris();
        $this->tambahPercobaan($order, 2);

        $this->postJson(route('midtrans.notification'), $this->payload())->assertOk();
        $this->postJson(route('midtrans.notification'), $this->payload([
            'order_id' => 'V7K2M9QX-P2',
            'transaction_id' => 'def-456',
        ]))->assertOk();

        $order->refresh();

        $this->assertSame(StatusPembayaran::PerluDiperiksa, $order->status_pembayaran);
        $this->assertSame(StatusPesanan::Antre, $order->status_pesanan);
        $this->assertSame(1, $order->nomor_antrean);
        $this->assertSame(1, QueueCounter::query()->firstOrFail()->nomor_terakhir);
    }

    public function test_signature_tidak_valid_ditolak_tanpa_mengubah_data(): void
    {
        $order = $this->orderQris();

        $this->postJson(route('midtrans.notification'), $this->payload(serverKey: 'kunci-salah'))
            ->assertForbidden();

        $order->refresh();

        $this->assertSame(StatusPembayaran::Menunggu, $order->status_pembayaran);
        $this->assertNull($order->nomor_antrean);
        $this->assertSame(0, PaymentNotification::query()->count());
    }

    public function test_nominal_tidak_cocok_ditandai_perlu_diperiksa_tanpa_nomor_antrean(): void
    {
        $order = $this->orderQris();

        $respons = $this->postJson(route('midtrans.notification'), $this->payload([
            'gross_amount' => '40000.00',
        ]));

        $respons->assertOk()->assertJson([
            'status_pemrosesan' => StatusPemrosesanNotifikasi::Diabaikan->value,
        ]);

        $order->refresh();

        $this->assertSame(StatusPembayaran::PerluDiperiksa, $order->status_pembayaran);
        $this->assertNull($order->nomor_antrean);
        $this->assertSame(0, QueueCounter::query()->count());
    }

    public function test_order_tidak_dikenal_dicatat_gagal_dan_tetap_200(): void
    {
        $order = $this->orderQris();

        $this->postJson(route('midtrans.notification'), $this->payload([
            'order_id' => 'TIDAKADA-P1',
        ]))->assertOk()->assertJson([
            'status_pemrosesan' => StatusPemrosesanNotifikasi::Gagal->value,
        ]);

        $order->refresh();

        $this->assertSame(StatusPembayaran::Menunggu, $order->status_pembayaran);
        $this->assertDatabaseHas('payment_notifications', [
            'midtrans_order_id' => 'TIDAKADA-P1',
            'status_pemrosesan' => StatusPemrosesanNotifikasi::Gagal->value,
        ]);
    }

    public function test_expire_menandai_kedaluwarsa_tanpa_nomor_antrean(): void
    {
        $order = $this->orderQris();

        $this->postJson(route('midtrans.notification'), $this->payload([
            'transaction_status' => 'expire',
            'status_code' => '407',
        ]))->assertOk();

        $order->refresh();

        $this->assertSame(StatusPembayaran::Kedaluwarsa, $order->status_pembayaran);
        $this->assertNull($order->nomor_antrean);
    }

    public function test_deny_menandai_gagal_tanpa_nomor_antrean(): void
    {
        $order = $this->orderQris();

        $this->postJson(route('midtrans.notification'), $this->payload([
            'transaction_status' => 'deny',
            'status_code' => '202',
        ]))->assertOk();

        $order->refresh();

        $this->assertSame(StatusPembayaran::Gagal, $order->status_pembayaran);
        $this->assertNull($order->nomor_antrean);
    }

    public function test_pending_tidak_mengubah_status_order(): void
    {
        $order = $this->orderQris();

        $this->postJson(route('midtrans.notification'), $this->payload([
            'transaction_status' => 'pending',
            'status_code' => '201',
        ]))->assertOk();

        $order->refresh();

        $this->assertSame(StatusPembayaran::Menunggu, $order->status_pembayaran);
        $this->assertSame(StatusPesanan::MenungguPembayaran, $order->status_pesanan);
        $this->assertNull($order->nomor_antrean);
    }

    public function test_settlement_setelah_pembatalan_lokal_tanpa_nomor_antrean(): void
    {
        $order = $this->orderQris();
        $order->update([
            'status_pembayaran' => StatusPembayaran::Dibatalkan,
            'status_pesanan' => StatusPesanan::Dibatalkan,
        ]);

        $this->postJson(route('midtrans.notification'), $this->payload())->assertOk();

        $order->refresh();

        $this->assertSame(StatusPembayaran::PerluDiperiksa, $order->status_pembayaran);
        $this->assertSame(StatusPesanan::Dibatalkan, $order->status_pesanan);
        $this->assertNull($order->nomor_antrean);
        $this->assertSame(0, QueueCounter::query()->count());
    }

    public function test_refund_pada_order_lunas_ditandai_perlu_diperiksa_dan_nomor_dipertahankan(): void
    {
        $order = $this->orderQris();

        $this->postJson(route('midtrans.notification'), $this->payload())->assertOk();

        $this->postJson(route('midtrans.notification'), $this->payload([
            'transaction_status' => 'refund',
        ]))->assertOk();

        $order->refresh();

        $this->assertSame(StatusPembayaran::PerluDiperiksa, $order->status_pembayaran);
        $this->assertSame(1, $order->nomor_antrean);
    }

    public function test_payload_tidak_lengkap_diabaikan(): void
    {
        $this->postJson(route('midtrans.notification'), ['order_id' => 'V7K2M9QX-P1'])
            ->assertOk();

        $this->assertSame(0, PaymentNotification::query()->count());
    }

    public function test_server_key_kosong_menjawab_500(): void
    {
        $order = $this->orderQris();

        config(['services.midtrans.server_key' => '']);

        $this->postJson(route('midtrans.notification'), $this->payload())
            ->assertStatus(500);

        $order->refresh();

        $this->assertSame(StatusPembayaran::Menunggu, $order->status_pembayaran);
        $this->assertSame(0, PaymentNotification::query()->count());
    }

    public function test_data_pembayaran_dan_notifikasi_tersimpan_untuk_audit(): void
    {
        $order = $this->orderQris();

        $this->postJson(route('midtrans.notification'), $this->payload())->assertOk();

        $payment = $order->payments()->firstOrFail();

        $this->assertSame('settlement', $payment->status_transaksi);
        $this->assertSame('200', $payment->status_code);
        $this->assertSame('abc-123', $payment->transaction_id);
        $this->assertSame('accept', $payment->fraud_status);
        $this->assertSame('other_qris', $payment->payment_type);
        $this->assertNotNull($payment->waktu_diproses);

        $notifikasi = PaymentNotification::query()->firstOrFail();

        $this->assertTrue($notifikasi->signature_valid);
        $this->assertSame($order->id, $notifikasi->order_id);
        $this->assertSame(StatusPemrosesanNotifikasi::Diproses, $notifikasi->status_pemrosesan);
        $this->assertSame('abc-123', $notifikasi->payload['transaction_id']);
        $this->assertNotNull($notifikasi->processed_at);
    }

    public function test_nomor_antrean_melanjutkan_penghitung_harian(): void
    {
        QueueCounter::create([
            'tanggal' => now()->toDateString(),
            'nomor_terakhir' => 4,
        ]);

        $order = $this->orderQris();

        $this->postJson(route('midtrans.notification'), $this->payload())->assertOk();

        $order->refresh();

        $this->assertSame(5, $order->nomor_antrean);
        $this->assertSame(5, QueueCounter::query()->firstOrFail()->nomor_terakhir);
    }
}

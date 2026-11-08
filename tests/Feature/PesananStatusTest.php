<?php

namespace Tests\Feature;

use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use App\Enums\StatusPesanan;
use App\Enums\SumberPesanan;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PesananStatusTest extends TestCase
{
    use RefreshDatabase;

    private const SNAP_URL = 'https://app.sandbox.midtrans.com/snap/v1/transactions';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.midtrans.server_key' => 'SB-Mid-server-test',
            'services.midtrans.client_key' => 'SB-Mid-client-test',
            'services.midtrans.is_production' => false,
            'services.midtrans.snap_sandbox_url' => self::SNAP_URL,
        ]);

        Carbon::setTestNow(Carbon::create(2026, 10, 31, 14, 0, 0, 'Asia/Jakarta'));
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
            'kode_pesanan' => 'V7K2M9QX',
            'nama_pelanggan' => 'Budi',
            'tipe_pesanan' => 'dine_in',
            'catatan' => 'Tanpa es',
            'sumber' => SumberPesanan::Qr,
            'status_pesanan' => StatusPesanan::MenungguPembayaran,
            'metode_pembayaran' => MetodePembayaran::Qris,
            'status_pembayaran' => StatusPembayaran::Menunggu,
            'total' => 45000,
            'batas_waktu_pembayaran' => now()->addMinutes(15),
        ], $ganti));

        $order->items()->create([
            'menu_id' => null,
            'nama_menu' => 'Vanilla Latte',
            'harga_satuan' => 18000,
            'jumlah' => 2,
            'subtotal' => 36000,
            'catatan' => null,
        ]);

        $order->items()->create([
            'menu_id' => null,
            'nama_menu' => 'Heejo',
            'harga_satuan' => 9000,
            'jumlah' => 1,
            'subtotal' => 9000,
            'catatan' => null,
        ]);

        return $order;
    }

    private function tambahPembayaran(
        Order $order,
        int $nomor = 1,
        string $status = 'pending',
        ?string $token = 'snap-token-1',
        ?Carbon $waktuDibuat = null,
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
            'waktu_dibuat' => $waktuDibuat ?? now(),
        ]);
    }

    private function fakeSnapSukses(string $token = 'snap-token-baru'): void
    {
        Http::fake([
            'app.sandbox.midtrans.com/*' => Http::response([
                'token' => $token,
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/baru',
            ], 201),
        ]);
    }

    public function test_halaman_status_menunggu_menampilkan_rincian_tanpa_nomor_antrean(): void
    {
        $order = $this->buatOrder();
        $this->tambahPembayaran($order);

        $this->get(route('pesanan.status', ['order' => $order->kode_pesanan]))
            ->assertOk()
            ->assertSee('Menunggu Pembayaran')
            ->assertSee('Budi')
            ->assertSee('Vanilla Latte')
            ->assertSee('Heejo')
            ->assertSee('Rp 45.000')
            ->assertSee('#--')
            ->assertSee('Buka Pembayaran QRIS')
            ->assertSee(route('pesanan.status.json', ['order' => $order->kode_pesanan]), false);
    }

    public function test_halaman_status_lunas_menampilkan_tiket_dan_nomor_antrean(): void
    {
        $order = $this->buatOrder([
            'status_pesanan' => StatusPesanan::Antre,
            'status_pembayaran' => StatusPembayaran::Lunas,
            'nomor_antrean' => 7,
            'tanggal_antrean' => now()->toDateString(),
            'waktu_lunas' => now(),
            'batas_waktu_pembayaran' => null,
        ]);
        $this->tambahPembayaran($order, 1, 'settlement', 'snap-token-1');

        $this->get(route('pesanan.status', ['order' => $order->kode_pesanan]))
            ->assertOk()
            ->assertSee('Pesanan Berhasil Dibuat!')
            ->assertSee('#007')
            ->assertSee('Lunas via QRIS')
            ->assertSee('Budi')
            ->assertSee('Vanilla Latte')
            ->assertSee('Rp 45.000');
    }

    public function test_halaman_pesanan_kasir_tidak_bisa_dibuka_dari_kode_publik(): void
    {
        $order = $this->buatOrder([
            'kode_pesanan' => 'KASIR123',
            'sumber' => SumberPesanan::Kasir,
            'metode_pembayaran' => MetodePembayaran::Tunai,
            'status_pesanan' => StatusPesanan::Antre,
            'status_pembayaran' => StatusPembayaran::Lunas,
            'nomor_antrean' => 1,
            'tanggal_antrean' => now()->toDateString(),
            'waktu_lunas' => now(),
        ]);

        $this->get(route('pesanan.status', ['order' => $order->kode_pesanan]))
            ->assertNotFound();
    }

    public function test_status_json_memuat_dapat_bayar_ulang_dan_tidak_membocorkan_server_key(): void
    {
        $order = $this->buatOrder();
        $this->tambahPembayaran($order);

        $this->getJson(route('pesanan.status.json', ['order' => $order->kode_pesanan]))
            ->assertOk()
            ->assertJsonPath('data.kode_pesanan', $order->kode_pesanan)
            ->assertJsonPath('data.status_pembayaran', 'menunggu')
            ->assertJsonPath('data.status_pesanan', 'menunggu_pembayaran')
            ->assertJsonPath('data.nomor_antrean', null)
            ->assertJsonPath('data.total', 45000)
            ->assertJsonPath('data.dapat_bayar_ulang', true)
            ->assertDontSee('SB-Mid-server-test', false);
    }

    public function test_status_json_lunas_memakai_nomor_tiga_digit(): void
    {
        $order = $this->buatOrder([
            'status_pesanan' => StatusPesanan::Antre,
            'status_pembayaran' => StatusPembayaran::Lunas,
            'nomor_antrean' => 7,
            'tanggal_antrean' => now()->toDateString(),
            'waktu_lunas' => now(),
            'batas_waktu_pembayaran' => null,
        ]);
        $this->tambahPembayaran($order, 1, 'settlement');

        $this->getJson(route('pesanan.status.json', ['order' => $order->kode_pesanan]))
            ->assertOk()
            ->assertJsonPath('data.nomor_antrean', '007')
            ->assertJsonPath('data.status_pembayaran', 'lunas')
            ->assertJsonPath('data.status_pesanan', 'antre')
            ->assertJsonPath('data.dapat_bayar_ulang', false);
    }

    public function test_bayar_lagi_membuka_token_aktif_tanpa_memanggil_midtrans_lagi(): void
    {
        $order = $this->buatOrder();
        $this->tambahPembayaran($order);

        $this->postJson(route('pesanan.bayar-lagi', ['order' => $order->kode_pesanan]))
            ->assertOk()
            ->assertJsonPath('data.snap_token', 'snap-token-1')
            ->assertJsonPath('data.kode_pesanan', $order->kode_pesanan);

        $this->assertDatabaseCount('payments', 1);
        Http::assertNothingSent();
    }

    public function test_bayar_lagi_setelah_gagal_membuat_percobaan_baru(): void
    {
        $order = $this->buatOrder();
        $this->tambahPembayaran($order, 1, 'failure', null);
        $this->fakeSnapSukses();

        $this->postJson(route('pesanan.bayar-lagi', ['order' => $order->kode_pesanan]))
            ->assertOk()
            ->assertJsonPath('data.snap_token', 'snap-token-baru')
            ->assertJsonPath('data.batas_waktu_pembayaran', now()->addMinutes(15)->toIso8601String());

        $order->refresh();

        $this->assertSame(2, $order->payments()->count());
        $this->assertSame(StatusPembayaran::Menunggu, $order->status_pembayaran);
        $this->assertSame(now()->addMinutes(15)->toDateString(), $order->batas_waktu_pembayaran->toDateString());

        Http::assertSent(function (Request $request) use ($order): bool {
            return $request->url() === self::SNAP_URL
                && $request->data()['transaction_details']['order_id'] === $order->kode_pesanan.'-P2'
                && $request->data()['callbacks']['finish'] === route('pesanan.status', ['order' => $order->kode_pesanan]);
        });
    }

    public function test_bayar_lagi_pending_kedaluwarsa_menandai_lama_dan_membuat_percobaan_baru(): void
    {
        $order = $this->buatOrder();
        $payment = $this->tambahPembayaran($order, 1, 'pending', 'snap-token-lama', now()->subMinutes(16));
        $this->fakeSnapSukses();

        $this->postJson(route('pesanan.bayar-lagi', ['order' => $order->kode_pesanan]))
            ->assertOk();

        $payment->refresh();

        $this->assertSame('kedaluwarsa', $payment->status_transaksi);
        $this->assertSame(2, $order->payments()->count());
    }

    public function test_bayar_lagi_ditolak_untuk_pesanan_lunas(): void
    {
        $order = $this->buatOrder([
            'status_pesanan' => StatusPesanan::Antre,
            'status_pembayaran' => StatusPembayaran::Lunas,
            'nomor_antrean' => 7,
            'tanggal_antrean' => now()->toDateString(),
            'waktu_lunas' => now(),
            'batas_waktu_pembayaran' => null,
        ]);
        $this->tambahPembayaran($order, 1, 'settlement');

        $this->postJson(route('pesanan.bayar-lagi', ['order' => $order->kode_pesanan]))
            ->assertStatus(409);

        $this->assertDatabaseCount('payments', 1);
        Http::assertNothingSent();
    }

    public function test_bayar_lagi_ditolak_untuk_pesanan_dibatalkan(): void
    {
        $order = $this->buatOrder([
            'status_pesanan' => StatusPesanan::Dibatalkan,
            'status_pembayaran' => StatusPembayaran::Dibatalkan,
            'batas_waktu_pembayaran' => null,
        ]);
        $this->tambahPembayaran($order, 1, 'cancel', null);

        $this->postJson(route('pesanan.bayar-lagi', ['order' => $order->kode_pesanan]))
            ->assertStatus(409);
    }

    public function test_bayar_lagi_ditolak_saat_percobaan_baru_sedang_dibuat(): void
    {
        $order = $this->buatOrder();
        $this->tambahPembayaran($order, 1, 'pending', null);

        $this->postJson(route('pesanan.bayar-lagi', ['order' => $order->kode_pesanan]))
            ->assertStatus(409);
    }

    public function test_bayar_lagi_menjawab_503_bila_client_key_snap_tidak_tersedia(): void
    {
        config(['services.midtrans.client_key' => null]);

        $order = $this->buatOrder();
        $this->tambahPembayaran($order);

        $this->postJson(route('pesanan.bayar-lagi', ['order' => $order->kode_pesanan]))
            ->assertStatus(503);

        $this->assertDatabaseCount('payments', 1);
        Http::assertNothingSent();
    }
}

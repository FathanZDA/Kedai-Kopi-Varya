<?php

namespace Tests\Feature;

use App\Enums\StatusPembayaran;
use App\Enums\StatusPesanan;
use App\Enums\SumberPesanan;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PelangganBCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private const SNAP_URL = 'https://app.sandbox.midtrans.com/snap/v1/transactions';

    private Menu $kopi;

    private Menu $teh;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.midtrans.server_key' => 'SB-Mid-server-test',
            'services.midtrans.client_key' => 'SB-Mid-client-test',
            'services.midtrans.is_production' => false,
            'services.midtrans.snap_sandbox_url' => self::SNAP_URL,
        ]);

        $category = Category::create(['nama' => 'Kopi', 'slug' => 'kopi', 'urutan' => 1]);

        $this->kopi = Menu::create([
            'category_id' => $category->id,
            'nama' => 'Americano Street',
            'slug' => 'americano-street',
            'harga' => 18000,
            'tersedia' => true,
            'urutan' => 1,
        ]);

        $this->teh = Menu::create([
            'category_id' => $category->id,
            'nama' => 'Matcha Creamy',
            'slug' => 'matcha-creamy',
            'harga' => 24000,
            'tersedia' => true,
            'urutan' => 2,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /**
     * @param  array<string, mixed>  $ganti
     * @return array<string, mixed>
     */
    private function payload(array $ganti = []): array
    {
        return array_merge([
            'nama_pelanggan' => 'Budi',
            'tipe_pesanan' => 'dine_in',
            'catatan' => 'Tanpa es',
            'item' => [
                ['menu_id' => $this->kopi->id, 'jumlah' => 2],
            ],
        ], $ganti);
    }

    private function fakeSnapSukses(): void
    {
        Http::fake([
            'app.sandbox.midtrans.com/*' => Http::response([
                'token' => 'snap-token-123',
                'redirect_url' => 'https://app.sandbox.midtrans.com/snap/v4/redirection/abc',
            ], 201),
        ]);
    }

    public function test_tamu_dapat_checkout_dan_mendapat_halaman_pembayaran(): void
    {
        $this->fakeSnapSukses();
        Carbon::setTestNow('2026-10-29 10:00:00');

        $respons = $this->postJson(route('pelanggan-b.checkout.store'), $this->payload());

        $respons->assertCreated()
            ->assertJsonPath('data.total', 36000)
            ->assertJsonPath('data.status_pesanan', 'menunggu_pembayaran')
            ->assertJsonPath('data.status_pembayaran', 'menunggu')
            ->assertJsonPath('data.snap_token', 'snap-token-123')
            ->assertJsonPath('data.snap_redirect_url', 'https://app.sandbox.midtrans.com/snap/v4/redirection/abc')
            ->assertJsonPath('data.batas_waktu_pembayaran', now()->addMinutes(15)->toIso8601String());

        $this->assertSame(8, mb_strlen($respons->json('data.kode_pesanan')));

        $order = Order::query()->sole();

        $this->assertSame(StatusPesanan::MenungguPembayaran, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Menunggu, $order->status_pembayaran);
        $this->assertSame(SumberPesanan::Qr, $order->sumber);
        $this->assertSame('qris', $order->metode_pembayaran->value);
        $this->assertNull($order->nomor_antrean);
        $this->assertNull($order->tanggal_antrean);
        $this->assertNull($order->cashier_id);
        $this->assertNull($order->waktu_lunas);

        // Nomor antrean baru dibuat setelah webhook menyatakan lunas.
        $this->assertDatabaseCount('queue_counters', 0);

        $payment = Payment::query()->sole();

        $this->assertSame($order->id, $payment->order_id);
        $this->assertSame($order->kode_pesanan.'-P1', $payment->midtrans_order_id);
        $this->assertSame('36000.00', $payment->gross_amount);
        $this->assertSame('snap-token-123', $payment->snap_token);
        $this->assertSame(
            'https://app.sandbox.midtrans.com/snap/v4/redirection/abc',
            $payment->snap_redirect_url,
        );
    }

    public function test_payload_snap_memakai_snapshot_dan_total_server(): void
    {
        $this->fakeSnapSukses();

        $this->postJson(route('pelanggan-b.checkout.store'), $this->payload())
            ->assertCreated();

        $order = Order::query()->sole();

        Http::assertSent(function (Request $request) use ($order): bool {
            $body = $request->data();

            return $request->url() === self::SNAP_URL
                && $body['transaction_details']['order_id'] === $order->kode_pesanan.'-P1'
                && $body['transaction_details']['gross_amount'] === 36000
                && $body['item_details'] === [[
                    'id' => (string) $this->kopi->id,
                    'price' => 18000,
                    'quantity' => 2,
                    'name' => 'Americano Street',
                ]]
                && $body['customer_details'] === ['first_name' => 'Budi']
                && $body['enabled_payments'] === ['other_qris']
                && $body['expiry'] === ['unit' => 'minutes', 'duration' => 15]
                && $body['callbacks'] === [
                    'finish' => route('pesanan.status', ['order' => $order->kode_pesanan]),
                ]
                && $request->hasHeader('Authorization');
        });
    }

    public function test_harga_dari_browser_diabaikan(): void
    {
        $this->fakeSnapSukses();

        $this->postJson(route('pelanggan-b.checkout.store'), $this->payload([
            'total' => 2,
            'item' => [
                [
                    'menu_id' => $this->kopi->id,
                    'jumlah' => 2,
                    'harga_satuan' => 1,
                    'subtotal' => 2,
                ],
            ],
        ]))
            ->assertCreated()
            ->assertJsonPath('data.total', 36000);

        $this->assertDatabaseHas('order_items', [
            'menu_id' => $this->kopi->id,
            'nama_menu' => 'Americano Street',
            'harga_satuan' => 18000,
            'jumlah' => 2,
            'subtotal' => 36000,
        ]);
    }

    public function test_menu_habis_ditolak_tanpa_membuat_order_atau_memanggil_midtrans(): void
    {
        $this->teh->update(['tersedia' => false]);
        $this->fakeSnapSukses();

        $this->postJson(route('pelanggan-b.checkout.store'), $this->payload([
            'item' => [['menu_id' => $this->teh->id, 'jumlah' => 1]],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('item');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        Http::assertNothingSent();
    }

    public function test_keranjang_kosong_ditolak(): void
    {
        $this->fakeSnapSukses();

        $this->postJson(route('pelanggan-b.checkout.store'), $this->payload(['item' => []]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('item');

        $this->assertDatabaseCount('orders', 0);
        Http::assertNothingSent();
    }

    public function test_input_pelanggan_tidak_valid_ditolak(): void
    {
        $this->fakeSnapSukses();

        $this->postJson(route('pelanggan-b.checkout.store'), [
            'nama_pelanggan' => '',
            'tipe_pesanan' => 'delivery',
            'item' => null,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nama_pelanggan',
                'tipe_pesanan',
                'item',
            ])
            ->assertJsonPath('errors.nama_pelanggan.0', 'Nama pelanggan wajib diisi.');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_snap_gagal_tetap_menyimpan_order_menunggu_dan_bisa_dibayar_ulang(): void
    {
        Http::fake([
            'app.sandbox.midtrans.com/*' => Http::response([
                'status_code' => '401',
                'status_message' => 'Access denied due to unauthorized transaction',
            ], 401),
        ]);

        $respons = $this->postJson(route('pelanggan-b.checkout.store'), $this->payload());

        $order = Order::query()->sole();

        $respons->assertStatus(500)
            ->assertJsonPath('data.kode_pesanan', $order->kode_pesanan);

        $this->assertSame(StatusPesanan::MenungguPembayaran, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Menunggu, $order->status_pembayaran);
        $this->assertNull($order->nomor_antrean);

        $payment = Payment::query()->sole();

        $this->assertSame($order->kode_pesanan.'-P1', $payment->midtrans_order_id);
        $this->assertSame('failure', $payment->status_transaksi);
        $this->assertSame('401', $payment->status_code);
        $this->assertNull($payment->snap_token);
        $this->assertNull($payment->snap_redirect_url);
        $this->assertNotNull($payment->waktu_diproses);
    }

    public function test_gangguan_jaringan_midtrans_ditangani_sebagai_500(): void
    {
        Http::fake([
            'app.sandbox.midtrans.com/*' => Http::failedConnection(),
        ]);

        $this->postJson(route('pelanggan-b.checkout.store'), $this->payload())
            ->assertStatus(500)
            ->assertJsonPath('data.kode_pesanan', Order::query()->sole()->kode_pesanan);

        $this->assertSame('failure', Payment::query()->sole()->status_transaksi);
    }

    public function test_checkout_ditolak_bila_kredensial_midtrans_belum_diatur(): void
    {
        config(['services.midtrans.server_key' => null]);
        $this->fakeSnapSukses();

        $this->postJson(route('pelanggan-b.checkout.store'), $this->payload())
            ->assertStatus(503)
            ->assertJsonPath('message', 'Pembayaran QRIS belum tersedia. Silakan hubungi kasir untuk memesan.');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        Http::assertNothingSent();
    }

    public function test_checkout_ditolak_bila_client_key_snap_belum_diatur(): void
    {
        config(['services.midtrans.client_key' => null]);
        $this->fakeSnapSukses();

        $this->postJson(route('pelanggan-b.checkout.store'), $this->payload())
            ->assertStatus(503)
            ->assertJsonPath('message', 'Pembayaran QRIS belum tersedia. Silakan hubungi kasir untuk memesan.');

        $this->assertDatabaseCount('orders', 0);
        Http::assertNothingSent();
    }

    public function test_checkout_dibatasi_sepuluh_percobaan_per_menit(): void
    {
        $this->fakeSnapSukses();

        for ($percobaan = 1; $percobaan <= 10; $percobaan++) {
            $this->postJson(route('pelanggan-b.checkout.store'), $this->payload())
                ->assertCreated();
        }

        $this->postJson(route('pelanggan-b.checkout.store'), $this->payload())
            ->assertStatus(429);

        $this->assertDatabaseCount('orders', 10);
    }

    public function test_halaman_checkout_menyiapkan_token_csrf_dan_route_penyimpanan(): void
    {
        $this->get(route('pelanggan-b.checkout'))
            ->assertOk()
            ->assertSee('name="csrf-token"', false)
            ->assertSee(route('pelanggan-b.checkout.store'), false)
            ->assertSee('https://app.sandbox.midtrans.com/snap/snap.js', false)
            ->assertSee('data-client-key="SB-Mid-client-test"', false)
            ->assertDontSee('SB-Mid-server-test', false);
    }
}

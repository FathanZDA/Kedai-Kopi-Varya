<?php

namespace Tests\Feature;

use App\Enums\StatusPembayaran;
use App\Enums\StatusPesanan;
use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Menu;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class KasirOrderTest extends TestCase
{
    use RefreshDatabase;

    private User $kasir;

    private Menu $kopi;

    private Menu $teh;

    protected function setUp(): void
    {
        parent::setUp();

        $this->kasir = User::factory()->create(['role' => UserRole::Kasir]);

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
            'nama_pelanggan' => 'Anjani',
            'tipe_pesanan' => 'dine_in',
            'catatan' => 'Less Sugar',
            'uang_diterima' => 50000,
            'item' => [
                ['menu_id' => $this->kopi->id, 'jumlah' => 2, 'catatan' => 'Tanpa gula'],
            ],
        ], $ganti);
    }

    public function test_kasir_dapat_membuat_order_tunai_yang_langsung_lunas(): void
    {
        $respons = $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.store'), $this->payload());

        $respons->assertCreated()
            ->assertJsonPath('data.nomor_antrean', '001')
            ->assertJsonPath('data.total', 36000)
            ->assertJsonPath('data.uang_diterima', 50000)
            ->assertJsonPath('data.kembalian', 14000)
            ->assertJsonPath('data.status_pesanan', 'antre')
            ->assertJsonPath('data.status_pembayaran', 'lunas')
            ->assertJsonPath('data.metode_pembayaran', 'tunai');

        $this->assertSame(8, mb_strlen($respons->json('data.kode_pesanan')));

        $order = Order::query()->sole();

        $this->assertSame($this->kasir->id, $order->cashier_id);
        $this->assertSame(StatusPesanan::Antre, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Lunas, $order->status_pembayaran);
        $this->assertSame(now()->toDateString(), $order->tanggal_antrean->toDateString());
        $this->assertNotNull($order->waktu_lunas);
    }

    public function test_harga_dan_total_dihitung_dari_database_bukan_dari_browser(): void
    {
        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.store'), $this->payload([
                'item' => [
                    [
                        'menu_id' => $this->kopi->id,
                        'jumlah' => 2,
                        'harga_satuan' => 1,
                        'subtotal' => 2,
                    ],
                ],
                'total' => 2,
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

    public function test_nomor_antrean_berurutan_dalam_satu_hari(): void
    {
        Carbon::setTestNow('2026-10-27 10:00:00');

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.store'), $this->payload())
            ->assertJsonPath('data.nomor_antrean', '001');

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.store'), $this->payload())
            ->assertJsonPath('data.nomor_antrean', '002');

        $this->assertDatabaseCount('orders', 2);
    }

    public function test_nomor_antrean_mulai_dari_satu_lagi_besok_harinya(): void
    {
        Carbon::setTestNow('2026-10-27 23:30:00');

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.store'), $this->payload())
            ->assertJsonPath('data.nomor_antrean', '001');

        Carbon::setTestNow('2026-10-28 08:00:00');

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.store'), $this->payload())
            ->assertJsonPath('data.nomor_antrean', '001');

        $this->assertDatabaseHas('orders', ['tanggal_antrean' => '2026-10-27']);
        $this->assertDatabaseHas('orders', ['tanggal_antrean' => '2026-10-28']);
    }

    public function test_uang_diterima_kurang_dari_total_ditolak(): void
    {
        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.store'), $this->payload(['uang_diterima' => 20000]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('uang_diterima');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('queue_counters', 0);
    }

    public function test_menu_habis_ditolak_dan_tidak_menyimpan_apa_pun(): void
    {
        $this->teh->update(['tersedia' => false]);

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.store'), $this->payload([
                'item' => [['menu_id' => $this->teh->id, 'jumlah' => 1]],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('item');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_menu_yang_sudah_dihapus_ditolak(): void
    {
        $this->teh->delete();

        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.store'), $this->payload([
                'item' => [['menu_id' => $this->teh->id, 'jumlah' => 1]],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('item');

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_input_tidak_valid_ditolak(): void
    {
        $this->actingAs($this->kasir)
            ->postJson(route('kasir.pesanan.store'), [
                'nama_pelanggan' => '',
                'tipe_pesanan' => 'delivery',
                'uang_diterima' => 10000,
                'item' => [],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nama_pelanggan',
                'tipe_pesanan',
                'item',
            ]);

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_admin_tidak_boleh_membuat_order_kasir(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->actingAs($admin)
            ->postJson(route('kasir.pesanan.store'), $this->payload())
            ->assertForbidden();

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_tamu_ditolak_dan_diarahkan_ke_login(): void
    {
        $this->postJson(route('kasir.pesanan.store'), $this->payload())
            ->assertUnauthorized();

        $this->assertDatabaseCount('orders', 0);
    }
}

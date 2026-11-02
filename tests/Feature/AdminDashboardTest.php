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
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private int $nomorAntreanCounter = 1;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-11-02 10:00:00', 'Asia/Jakarta'));

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    }

    /**
     * @param  array<string, mixed>  $ganti
     */
    private function buatOrder(array $ganti = []): Order
    {
        $dibuat = $ganti['dibuat_pada'] ?? '2026-11-02 09:00:00';
        unset($ganti['dibuat_pada']);

        $order = Order::create(array_merge([
            'kode_pesanan' => strtoupper(Str::random(8)),
            'nomor_antrean' => $this->nomorAntreanCounter++,
            'tanggal_antrean' => '2026-11-02',
            'nama_pelanggan' => 'Anjani',
            'tipe_pesanan' => TipePesanan::DineIn,
            'sumber' => SumberPesanan::Kasir,
            'status_pesanan' => StatusPesanan::Antre,
            'metode_pembayaran' => MetodePembayaran::Tunai,
            'status_pembayaran' => StatusPembayaran::Lunas,
            'total' => 22000,
            'uang_diterima' => 25000,
            'kembalian' => 3000,
            'cashier_id' => $this->admin->id,
            'waktu_lunas' => Carbon::parse('2026-11-02 09:00:00', 'Asia/Jakarta'),
        ], $ganti));

        $waktu = Carbon::parse($dibuat, 'Asia/Jakarta');
        $order->forceFill(['created_at' => $waktu, 'updated_at' => $waktu])->save();

        return $order->refresh();
    }

    private function tambahItem(Order $order, string $nama = 'Varya Signature', int $harga = 22000, int $jumlah = 1): void
    {
        $order->items()->create([
            'menu_id' => null,
            'nama_menu' => $nama,
            'harga_satuan' => $harga,
            'jumlah' => $jumlah,
            'subtotal' => $harga * $jumlah,
        ]);
    }

    public function test_dashboard_menampilkan_ringkasan_dari_database(): void
    {
        $tunai = $this->buatOrder([
            'kode_pesanan' => 'TUNDASH1',
            'nama_pelanggan' => 'Budi',
            'total' => 22000,
        ]);
        $this->tambahItem($tunai);

        $qris = $this->buatOrder([
            'kode_pesanan' => 'QRDASH01',
            'nama_pelanggan' => 'Nadia',
            'metode_pembayaran' => MetodePembayaran::Qris,
            'sumber' => SumberPesanan::Qr,
            'total' => 50000,
            'uang_diterima' => null,
            'kembalian' => null,
        ]);
        $this->tambahItem($qris, 'Matcha Creamy', 25000, 2);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Ringkasan Dashboard')
            ->assertSee('TUNDASH1')
            ->assertSee('QRDASH01')
            ->assertSee('November 2026')
            ->assertDontSee('demo-order', false)
            ->assertDontSee('+14%');

        $this->assertSame(72000, $response->viewData('todayRevenue'));
        $this->assertSame(72000, $response->viewData('summary')['revenue']);
        $this->assertSame(2, $response->viewData('summary')['count']);
        $this->assertSame(2, $response->viewData('todayOrders'));
    }

    public function test_pendapatan_hanya_menghitung_transaksi_lunas(): void
    {
        $this->buatOrder(['kode_pesanan' => 'LUNASDS1', 'total' => 40000]);

        $this->buatOrder([
            'kode_pesanan' => 'NUNGGUDS',
            'nomor_antrean' => null,
            'tanggal_antrean' => null,
            'status_pesanan' => StatusPesanan::MenungguPembayaran,
            'status_pembayaran' => StatusPembayaran::Menunggu,
            'metode_pembayaran' => MetodePembayaran::Qris,
            'sumber' => SumberPesanan::Qr,
            'total' => 99000,
            'uang_diterima' => null,
            'kembalian' => null,
            'waktu_lunas' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk();

        $this->assertSame(40000, $response->viewData('todayRevenue'));
        $this->assertSame(1, $response->viewData('summary')['count']);
        $this->assertSame(40000, $response->viewData('summary')['revenue']);
        $this->assertSame(0.0, (float) $response->viewData('summary')['qris_percent']);
    }

    public function test_pesanan_menunggu_dan_diproses_dihitung_hari_ini(): void
    {
        $this->buatOrder([
            'kode_pesanan' => 'NUNGGUH1',
            'nomor_antrean' => null,
            'tanggal_antrean' => null,
            'status_pesanan' => StatusPesanan::MenungguPembayaran,
            'status_pembayaran' => StatusPembayaran::Menunggu,
            'metode_pembayaran' => MetodePembayaran::Qris,
            'sumber' => SumberPesanan::Qr,
            'waktu_lunas' => null,
        ]);

        $this->buatOrder([
            'kode_pesanan' => 'DIPROSES',
            'status_pesanan' => StatusPesanan::Diproses,
        ]);

        // Kemarin: tidak ikut hitungan operasional hari ini.
        $this->buatOrder([
            'kode_pesanan' => 'KEMARIN1',
            'nomor_antrean' => null,
            'tanggal_antrean' => null,
            'status_pesanan' => StatusPesanan::MenungguPembayaran,
            'status_pembayaran' => StatusPembayaran::Menunggu,
            'metode_pembayaran' => MetodePembayaran::Qris,
            'sumber' => SumberPesanan::Qr,
            'waktu_lunas' => null,
            'dibuat_pada' => '2026-11-01 09:00:00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSeeInOrder(['&#9679; 1 menunggu bayar'], false)
            ->assertSeeInOrder(['&#9679; 1 diproses'], false);

        $this->assertSame(1, $response->viewData('pendingPayments'));
        $this->assertSame(1, $response->viewData('processingCount'));
    }

    public function test_perbandingan_jumlah_pesanan_dengan_kemarin(): void
    {
        $this->buatOrder(['kode_pesanan' => 'HARINI01', 'dibuat_pada' => '2026-11-02 08:00:00']);
        $this->buatOrder(['kode_pesanan' => 'HARINI02', 'dibuat_pada' => '2026-11-02 09:00:00']);
        $this->buatOrder(['kode_pesanan' => 'KEMARIN2', 'dibuat_pada' => '2026-11-01 09:00:00']);

        $this->actingAs($this->admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('100,0%')
            ->assertSee('dibanding kemarin (1 pesanan)');
    }

    public function test_menu_terlaris_dari_snapshot_item_transaksi_lunas_bulan_ini(): void
    {
        $order = $this->buatOrder(['kode_pesanan' => 'MENUDASH']);
        $this->tambahItem($order, 'Varya Signature', 22000, 3);
        $this->tambahItem($order, 'Matcha Creamy', 24000, 1);

        $kemarinBulanLalu = $this->buatOrder([
            'kode_pesanan' => 'BULANLAL',
            'dibuat_pada' => '2026-10-20 09:00:00',
        ]);
        $this->tambahItem($kemarinBulanLalu, 'Cold Brew Grit', 30000, 9);

        $belumLunas = $this->buatOrder([
            'kode_pesanan' => 'BELUMLUN',
            'nomor_antrean' => null,
            'tanggal_antrean' => null,
            'status_pesanan' => StatusPesanan::MenungguPembayaran,
            'status_pembayaran' => StatusPembayaran::Menunggu,
            'metode_pembayaran' => MetodePembayaran::Qris,
            'sumber' => SumberPesanan::Qr,
            'waktu_lunas' => null,
        ]);
        $this->tambahItem($belumLunas, 'Truffle Fries', 22000, 8);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Menu Terlaris Bulan Ini')
            ->assertSee('Varya Signature')
            ->assertSee('3x terjual')
            ->assertSee('Matcha Creamy')
            ->assertSee('1x terjual')
            ->assertDontSee('Cold Brew Grit')
            ->assertDontSee('Truffle Fries')
            ->assertDontSee('9x terjual');
    }

    public function test_transaksi_terbaru_maksimal_sepuluh_urut_waktu(): void
    {
        foreach (range(1, 11) as $nomor) {
            $this->buatOrder([
                'kode_pesanan' => 'TRX'.str_pad((string) $nomor, 5, '0', STR_PAD_LEFT),
                'dibuat_pada' => '2026-11-02 '.str_pad((string) $nomor, 2, '0', STR_PAD_LEFT).':00:00',
            ]);
        }

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('TRX00011')
            ->assertSee('TRX00002')
            ->assertDontSee('TRX00001');
    }

    public function test_tamu_diarahkan_ke_login_dan_kasir_tidak_bisa_membuka_dashboard(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $kasir = User::factory()->create(['role' => UserRole::Kasir]);

        $this->actingAs($kasir)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}

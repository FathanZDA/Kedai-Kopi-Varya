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
            ->assertSee('Grafik Penjualan')
            ->assertSee('November 2026')
            ->assertSee('Rp 72.000')
            ->assertDontSee('Pesanan Masuk Terbaru')
            ->assertDontSee('TUNDASH1')
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
            ->assertSee('Paling Banyak Terjual')
            ->assertSee('Varya Signature')
            ->assertSee('3x terjual')
            ->assertSee('Matcha Creamy')
            ->assertSee('1x terjual')
            ->assertDontSee('Cold Brew Grit')
            ->assertDontSee('Truffle Fries')
            ->assertDontSee('9x terjual');
    }

    public function test_grafik_penjualan_harian_memakai_24_jam_dan_hanya_lunas(): void
    {
        $this->buatOrder([
            'kode_pesanan' => 'GRAFHAR1',
            'total' => 30000,
            'dibuat_pada' => '2026-11-02 09:15:00',
        ]);

        $this->buatOrder([
            'kode_pesanan' => 'GRAFHAR2',
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
            'dibuat_pada' => '2026-11-02 09:45:00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Grafik Penjualan')
            ->assertSee('Total Hari Ini')
            ->assertSee('per jam')
            ->assertDontSee('Pesanan Masuk Terbaru')
            ->assertDontSee('GRAFHAR1');

        $grafik = $response->viewData('grafik');

        $this->assertSame('hari', $grafik['periode']);
        $this->assertCount(24, $grafik['batang']);
        $this->assertSame('09', $grafik['batang'][9]['label']);
        $this->assertSame('Pukul 09.00-09.59 WIB', $grafik['batang'][9]['judul']);
        $this->assertSame(30000, $grafik['batang'][9]['pendapatan']);
        $this->assertSame(2, $grafik['batang'][9]['pesanan']);
        $this->assertSame(100, $grafik['batang'][9]['tinggi']);
        $this->assertSame(0, $grafik['batang'][10]['pendapatan']);
        $this->assertSame(0, $grafik['batang'][10]['tinggi']);
        $this->assertSame(30000, $grafik['maks']);
    }

    public function test_grafik_mingguan_memakai_tujuh_hari_senin_sampai_minggu(): void
    {
        $this->buatOrder(['kode_pesanan' => 'GRAFSEN1', 'total' => 22000, 'dibuat_pada' => '2026-11-02 08:00:00']);
        $this->buatOrder(['kode_pesanan' => 'GRAFRAB1', 'total' => 50000, 'dibuat_pada' => '2026-11-04 12:00:00']);
        // Minggu sebelumnya tidak ikut minggu berjalan.
        $this->buatOrder(['kode_pesanan' => 'GRAFLALU', 'total' => 70000, 'dibuat_pada' => '2026-11-01 09:00:00']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard', ['periode' => 'minggu']));

        $response->assertOk()
            ->assertSee('Grafik Penjualan')
            ->assertSee('per hari')
            ->assertSee('Total Minggu Ini');

        $grafik = $response->viewData('grafik');

        $this->assertSame('minggu', $grafik['periode']);
        $this->assertCount(7, $grafik['batang']);
        $this->assertSame(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'], array_column($grafik['batang'], 'label'));
        $this->assertSame(22000, $grafik['batang'][0]['pendapatan']);
        $this->assertSame(50000, $grafik['batang'][2]['pendapatan']);
        $this->assertSame(1, $grafik['batang'][2]['pesanan']);
        $this->assertSame(0, $grafik['batang'][6]['pendapatan']);
        $this->assertSame(50000, $grafik['maks']);
    }

    public function test_grafik_bulanan_memakai_semua_tanggal_bulan_berjalan(): void
    {
        $this->buatOrder(['kode_pesanan' => 'GRAFNOV1', 'total' => 22000, 'dibuat_pada' => '2026-11-02 09:00:00']);
        $this->buatOrder(['kode_pesanan' => 'GRAFKTOB', 'total' => 99000, 'dibuat_pada' => '2026-10-20 09:00:00']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard', ['periode' => 'bulan']));

        $response->assertOk()
            ->assertSee('per tanggal')
            ->assertSee('Total Bulan Ini');

        $grafik = $response->viewData('grafik');

        $this->assertSame('bulan', $grafik['periode']);
        $this->assertCount(30, $grafik['batang']);
        $this->assertSame('1', $grafik['batang'][0]['label']);
        $this->assertSame('', $grafik['batang'][1]['label']);
        $this->assertSame('5', $grafik['batang'][4]['label']);
        $this->assertSame(0, $grafik['batang'][0]['pendapatan']);
        $this->assertSame(22000, $grafik['batang'][1]['pendapatan']);
        $this->assertSame(22000, $grafik['maks']);
    }

    public function test_dashboard_default_memakai_periode_hari_dan_menampilkan_tiga_tautan(): void
    {
        $this->buatOrder(['kode_pesanan' => 'HARIDEF1', 'dibuat_pada' => '2026-11-02 09:00:00']);
        $this->buatOrder(['kode_pesanan' => 'KEMARDEF', 'dibuat_pada' => '2026-11-01 09:00:00']);

        $response = $this->actingAs($this->admin)->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Hari Ini')
            ->assertSee('Minggu Ini')
            ->assertSee('Bulan Ini')
            ->assertSee(route('admin.dashboard', ['periode' => 'hari']), false)
            ->assertSee(route('admin.dashboard', ['periode' => 'minggu']), false)
            ->assertSee(route('admin.dashboard', ['periode' => 'bulan']), false);

        $this->assertSame('hari', $response->viewData('periodeAktif'));
        $this->assertSame('hari', $response->viewData('ringkasan')['periode']);
        $this->assertSame(1, $response->viewData('ringkasan')['orders']);
        $this->assertSame(1, $response->viewData('ringkasanPeriode')['minggu']['orders']);
        $this->assertSame(2, $response->viewData('ringkasanPeriode')['bulan']['orders']);
        $this->assertMatchesRegularExpression(
            '/data-periode="hari"\s+aria-current="page"/',
            $response->getContent()
        );
    }

    public function test_periode_hari_hanya_menghitung_order_hari_ini(): void
    {
        $this->buatOrder(['kode_pesanan' => 'HARIHARI', 'dibuat_pada' => '2026-11-02 09:00:00']);
        $this->buatOrder(['kode_pesanan' => 'KEMARINH', 'dibuat_pada' => '2026-11-01 09:00:00']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard', ['periode' => 'hari']));

        $response->assertOk();

        $ringkasan = $response->viewData('ringkasan');

        $this->assertSame('hari', $ringkasan['periode']);
        $this->assertSame(1, $ringkasan['orders']);
        $this->assertSame(1, $ringkasan['count']);
        $this->assertSame(22000, $ringkasan['revenue']);
        $this->assertSame(1, $ringkasan['pembanding']);
        $this->assertSame(0.0, (float) $ringkasan['change_percent']);
    }

    public function test_periode_minggu_memakai_senin_sampai_minggu(): void
    {
        // Senin berjalan ikut; Minggu kemarin dan Senin minggu lalu tidak ikut.
        $this->buatOrder(['kode_pesanan' => 'SENINW01', 'dibuat_pada' => '2026-11-02 09:00:00']);
        $this->buatOrder(['kode_pesanan' => 'MINGGU01', 'dibuat_pada' => '2026-11-01 09:00:00']);
        $this->buatOrder(['kode_pesanan' => 'SENINLW1', 'dibuat_pada' => '2026-10-26 09:00:00']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard', ['periode' => 'minggu']));

        $response->assertOk()
            ->assertSee('Minggu Ini');

        $ringkasan = $response->viewData('ringkasan');

        $this->assertSame('minggu', $ringkasan['periode']);
        $this->assertSame(1, $ringkasan['orders']);
        $this->assertSame(1, $ringkasan['count']);
        $this->assertSame(22000, $ringkasan['revenue']);
        $this->assertSame(2, $ringkasan['pembanding']);
        $this->assertSame(-50.0, (float) $ringkasan['change_percent']);
        $this->assertMatchesRegularExpression(
            '/data-periode="minggu"\s+aria-current="page"/',
            $response->getContent()
        );
    }

    public function test_periode_bulan_hanya_menghitung_bulan_berjalan(): void
    {
        $this->buatOrder(['kode_pesanan' => 'NOVEMBER', 'dibuat_pada' => '2026-11-02 09:00:00']);
        $this->buatOrder(['kode_pesanan' => 'OKTOBER1', 'dibuat_pada' => '2026-10-20 09:00:00']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard', ['periode' => 'bulan']));

        $response->assertOk()
            ->assertSee('Bulan Ini');

        $ringkasan = $response->viewData('ringkasan');

        $this->assertSame('bulan', $ringkasan['periode']);
        $this->assertSame(1, $ringkasan['orders']);
        $this->assertSame(1, $ringkasan['count']);
        $this->assertSame(22000, $ringkasan['revenue']);
        $this->assertSame(1, $ringkasan['pembanding']);
        $this->assertSame(0.0, (float) $ringkasan['change_percent']);
    }

    public function test_periode_tidak_valid_jatuh_ke_hari_ini_tanpa_error(): void
    {
        $this->buatOrder(['kode_pesanan' => 'VALIDHR1', 'dibuat_pada' => '2026-11-02 09:00:00']);
        $this->buatOrder(['kode_pesanan' => 'KEMARINV', 'dibuat_pada' => '2026-11-01 09:00:00']);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.dashboard', ['periode' => 'tahun']));

        $response->assertOk();
        $this->assertSame('hari', $response->viewData('periodeAktif'));
        $this->assertSame(1, $response->viewData('ringkasan')['orders']);

        // Query array juga tidak boleh menghasilkan error.
        $this->actingAs($this->admin)
            ->get(route('admin.dashboard').'?periode[]=minggu')
            ->assertOk()
            ->assertViewHas('periodeAktif', 'hari');
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

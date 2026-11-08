<?php

namespace Tests\Feature;

use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use App\Enums\StatusPesanan;
use App\Enums\SumberPesanan;
use App\Enums\TipePesanan;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminReportTest extends TestCase
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
        $dibuat = $ganti['dibuat_pada'] ?? '2026-11-01 10:00:00';
        unset($ganti['dibuat_pada']);

        $nomorAntrean = $ganti['nomor_antrean'] ?? $this->nomorAntreanCounter++;

        $order = Order::create(array_merge([
            'kode_pesanan' => strtoupper(Str::random(8)),
            'nomor_antrean' => $nomorAntrean,
            'tanggal_antrean' => '2026-11-01',
            'nama_pelanggan' => 'Anjani',
            'tipe_pesanan' => TipePesanan::DineIn,
            'sumber' => SumberPesanan::Kasir,
            'status_pesanan' => StatusPesanan::Selesai,
            'metode_pembayaran' => MetodePembayaran::Tunai,
            'status_pembayaran' => StatusPembayaran::Lunas,
            'total' => 22000,
            'uang_diterima' => 25000,
            'kembalian' => 3000,
            'cashier_id' => $this->admin->id,
            'waktu_lunas' => Carbon::parse('2026-11-01 10:00:00', 'Asia/Jakarta'),
        ], $ganti));

        $waktu = Carbon::parse($dibuat, 'Asia/Jakarta');
        $order->forceFill(['created_at' => $waktu, 'updated_at' => $waktu])->save();

        return $order->refresh();
    }

    private function tambahItem(Order $order, string $nama = 'Varya Signature', int $harga = 22000, int $jumlah = 1): OrderItem
    {
        return $order->items()->create([
            'menu_id' => null,
            'nama_menu' => $nama,
            'harga_satuan' => $harga,
            'jumlah' => $jumlah,
            'subtotal' => $harga * $jumlah,
        ]);
    }

    public function test_halaman_laporan_menampilkan_ringkasan_dan_transaksi_dari_database(): void
    {
        $qris = $this->buatOrder([
            'kode_pesanan' => 'QRS11111',
            'nomor_antrean' => 7,
            'nama_pelanggan' => 'Nadia',
            'metode_pembayaran' => MetodePembayaran::Qris,
            'sumber' => SumberPesanan::Qr,
            'total' => 50000,
            'uang_diterima' => null,
            'kembalian' => null,
            'dibuat_pada' => '2026-11-01 09:00:00',
        ]);
        $this->tambahItem($qris, 'Varya Signature', 22000, 2);

        $this->buatOrder([
            'kode_pesanan' => 'TUN22222',
            'nomor_antrean' => 8,
            'nama_pelanggan' => 'Budi',
            'total' => 22000,
            'dibuat_pada' => '2026-11-02 09:30:00',
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reports'));

        $response->assertOk()
            ->assertSee('Laporan Riwayat Transaksi')
            ->assertSee('Periode: November 2026')
            ->assertSee('Rp 72.000')
            ->assertSee('Rp 50.000')
            ->assertSee('Rp 22.000')
            ->assertSee('QRS11111')
            ->assertSee('TUN22222')
            ->assertSee('data-report-month', false);

        $response->assertSeeInOrder(['data-report-count', '>2</strong>'], false);
    }

    public function test_hanya_transaksi_lunas_yang_dihitung_sebagai_pendapatan(): void
    {
        $this->buatOrder([
            'kode_pesanan' => 'LUNAS001',
            'total' => 50000,
            'uang_diterima' => 50000,
            'kembalian' => 0,
        ]);

        $this->buatOrder([
            'kode_pesanan' => 'NUNGGU01',
            'nomor_antrean' => null,
            'status_pesanan' => StatusPesanan::MenungguPembayaran,
            'status_pembayaran' => StatusPembayaran::Menunggu,
            'metode_pembayaran' => MetodePembayaran::Qris,
            'sumber' => SumberPesanan::Qr,
            'total' => 30000,
            'uang_diterima' => null,
            'kembalian' => null,
            'waktu_lunas' => null,
        ]);

        $this->buatOrder([
            'kode_pesanan' => 'PERIKSA1',
            'nomor_antrean' => null,
            'status_pesanan' => StatusPesanan::MenungguPembayaran,
            'status_pembayaran' => StatusPembayaran::PerluDiperiksa,
            'metode_pembayaran' => MetodePembayaran::Qris,
            'sumber' => SumberPesanan::Qr,
            'total' => 20000,
            'uang_diterima' => null,
            'kembalian' => null,
            'waktu_lunas' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.reports'));

        $response->assertOk()
            ->assertSee('Rp 50.000')
            ->assertDontSee('Rp 100.000')
            ->assertDontSee('Rp 70.000')
            // Transaksi belum lunas tetap tampil sebagai informasi.
            ->assertSee('NUNGGU01')
            ->assertSee('PERIKSA1');

        $response->assertSeeInOrder(['data-report-count', '>1</strong>'], false);
    }

    public function test_filter_bulan_memilih_data_dan_ringkasan_bulan_tersebut(): void
    {
        $this->buatOrder([
            'kode_pesanan' => 'OKTOBER1',
            'total' => 40000,
            'uang_diterima' => 50000,
            'kembalian' => 10000,
            'dibuat_pada' => '2026-10-15 12:00:00',
        ]);

        $this->buatOrder([
            'kode_pesanan' => 'NOVEMBER',
            'total' => 72000,
            'uang_diterima' => 100000,
            'kembalian' => 28000,
            'dibuat_pada' => '2026-11-01 12:00:00',
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.reports', ['bulan' => '2026-10']));

        $response->assertOk()
            ->assertSee('Periode: Oktober 2026')
            ->assertSee('Rp 40.000')
            ->assertSee('OKTOBER1')
            ->assertDontSee('NOVEMBER')
            ->assertDontSee('Rp 72.000');
    }

    public function test_bulan_tidak_valid_jatuh_ke_bulan_berjalan(): void
    {
        $this->buatOrder(['kode_pesanan' => 'BULANINI', 'total' => 33000]);

        $this->actingAs($this->admin)
            ->get(route('admin.reports', ['bulan' => '2026-13']))
            ->assertOk()
            ->assertSee('Periode: November 2026')
            ->assertSee('BULANINI')
            ->assertSee('Rp 33.000');
    }

    public function test_pilihan_bulan_memuat_bulan_transaksi_dan_bulan_berjalan(): void
    {
        $this->buatOrder([
            'kode_pesanan' => 'JULI2026',
            'dibuat_pada' => '2026-07-04 12:00:00',
        ]);

        $this->actingAs($this->admin)
            ->get(route('admin.reports'))
            ->assertOk()
            ->assertSee('value="2026-07"', false)
            ->assertSee('value="2026-11"', false)
            ->assertSee('Juli 2026');
    }

    public function test_rincian_transaksi_json_memakai_snapshot_dan_data_tunai(): void
    {
        $order = $this->buatOrder([
            'kode_pesanan' => 'DETAIL01',
            'nomor_antrean' => 7,
            'nama_pelanggan' => 'Budi',
            'total' => 44000,
            'uang_diterima' => 50000,
            'kembalian' => 6000,
        ]);
        $this->tambahItem($order, 'Varya Signature', 22000, 2);

        $this->actingAs($this->admin)
            ->getJson(route('admin.reports.show', $order))
            ->assertOk()
            ->assertJsonPath('data.code', 'DETAIL01')
            ->assertJsonPath('data.queue', '007')
            ->assertJsonPath('data.customer', 'Budi')
            ->assertJsonPath('data.method', 'tunai')
            ->assertJsonPath('data.status', 'lunas')
            ->assertJsonPath('data.cash_received', 50000)
            ->assertJsonPath('data.change', 6000)
            ->assertJsonPath('data.total', 44000)
            ->assertJsonPath('data.items.0.name', 'Varya Signature')
            ->assertJsonPath('data.items.0.qty', 2)
            ->assertJsonPath('data.items.0.subtotal', 44000);
    }

    public function test_rincian_qris_menampilkan_id_midtrans(): void
    {
        $order = $this->buatOrder([
            'kode_pesanan' => 'QRISDET1',
            'metode_pembayaran' => MetodePembayaran::Qris,
            'sumber' => SumberPesanan::Qr,
            'total' => 50000,
            'uang_diterima' => null,
            'kembalian' => null,
        ]);

        $order->payments()->create([
            'midtrans_order_id' => 'QRISDET1-P1',
            'transaction_id' => 'trx-midtrans-123',
            'status_transaksi' => 'settlement',
            'status_code' => '200',
            'fraud_status' => 'accept',
            'gross_amount' => 50000,
            'payment_type' => 'other_qris',
            'waktu_dibuat' => Carbon::parse('2026-11-01 09:00:00', 'Asia/Jakarta'),
        ]);

        $this->actingAs($this->admin)
            ->getJson(route('admin.reports.show', $order))
            ->assertOk()
            ->assertJsonPath('data.method', 'qris')
            ->assertJsonPath('data.midtrans_transaction_id', 'trx-midtrans-123')
            ->assertJsonPath('data.midtrans_order_id', 'QRISDET1-P1')
            ->assertJsonPath('data.payment_type', 'other_qris');
    }

    public function test_rincian_dibatasi_role_admin_dan_order_tidak_dikenal_404(): void
    {
        $order = $this->buatOrder(['kode_pesanan' => 'AKSES001']);

        $this->get(route('admin.reports.show', $order))
            ->assertRedirect(route('login'));

        $kasir = User::factory()->create(['role' => UserRole::Kasir]);

        $this->actingAs($kasir)
            ->getJson(route('admin.reports.show', $order))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->getJson(route('admin.reports.show', 999999))
            ->assertNotFound();
    }

    public function test_tamu_diarahkan_ke_login_dan_kasir_tidak_bisa_membuka_laporan(): void
    {
        $this->get(route('admin.reports'))->assertRedirect(route('login'));

        $kasir = User::factory()->create(['role' => UserRole::Kasir]);

        $this->actingAs($kasir)
            ->get(route('admin.reports'))
            ->assertForbidden();
    }
}

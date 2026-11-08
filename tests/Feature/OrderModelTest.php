<?php

namespace Tests\Feature;

use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use App\Enums\StatusPesanan;
use App\Enums\SumberPesanan;
use App\Enums\TipePesanan;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderModelTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<string, mixed>  $atribut
     */
    private function buatOrder(array $atribut = []): Order
    {
        return Order::create(array_merge([
            'kode_pesanan' => 'AB12CD34',
            'nama_pelanggan' => 'Pelanggan Uji',
            'tipe_pesanan' => TipePesanan::DineIn,
            'sumber' => SumberPesanan::Kasir,
            'metode_pembayaran' => MetodePembayaran::Tunai,
            'total' => 44000,
        ], $atribut));
    }

    public function test_status_dibaca_sebagai_enum_dengan_default_dari_database(): void
    {
        $order = $this->buatOrder()->fresh();

        $this->assertSame(StatusPesanan::MenungguPembayaran, $order->status_pesanan);
        $this->assertSame(StatusPembayaran::Menunggu, $order->status_pembayaran);
        $this->assertSame(TipePesanan::DineIn, $order->tipe_pesanan);
        $this->assertSame(SumberPesanan::Kasir, $order->sumber);
        $this->assertSame(MetodePembayaran::Tunai, $order->metode_pembayaran);
    }

    public function test_enum_disimpan_sebagai_nilai_string_sesuai_kolom_database(): void
    {
        $order = $this->buatOrder([
            'status_pesanan' => StatusPesanan::Antre,
            'status_pembayaran' => StatusPembayaran::Lunas,
            'waktu_lunas' => now(),
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status_pesanan' => 'antre',
            'status_pembayaran' => 'lunas',
            'tipe_pesanan' => 'dine_in',
            'sumber' => 'kasir',
            'metode_pembayaran' => 'tunai',
        ]);
    }

    public function test_item_menyimpan_snapshot_dan_terhubung_ke_order(): void
    {
        $order = $this->buatOrder();

        $item = $order->items()->create([
            'menu_id' => null,
            'nama_menu' => 'Varya Signature',
            'harga_satuan' => 22000,
            'jumlah' => 2,
            'subtotal' => 44000,
            'catatan' => 'Tanpa gula',
        ]);

        $this->assertTrue($item->order->is($order));
        $this->assertSame('Varya Signature', $item->nama_menu);
        $this->assertSame(22000, $item->harga_satuan);
        $this->assertSame(2, $item->jumlah);
        $this->assertSame(44000, $item->subtotal);
        $this->assertSame('Tanpa gula', $item->catatan);
        $this->assertCount(1, $order->items);
    }

    public function test_menghapus_order_ikut_menghapus_item(): void
    {
        $order = $this->buatOrder();
        $order->items()->create([
            'menu_id' => null,
            'nama_menu' => 'Varya Signature',
            'harga_satuan' => 22000,
            'jumlah' => 1,
            'subtotal' => 22000,
        ]);

        $order->delete();

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }
}

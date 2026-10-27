<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ringkasan order untuk konfirmasi kasir setelah transaksi tersimpan.
 *
 * Kontrak mengikuti docs/ROUTES.md §4.4. `nomor_antrean` dikirim sebagai
 * string tiga digit ("007") karena itu yang ditampilkan dialog kasir.
 *
 * @mixin Order
 */
class OrderResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kode_pesanan' => $this->kode_pesanan,
            'nomor_antrean' => str_pad((string) $this->nomor_antrean, 3, '0', STR_PAD_LEFT),
            'nama_pelanggan' => $this->nama_pelanggan,
            'tipe_pesanan' => $this->tipe_pesanan->value,
            'tipe_pesanan_label' => $this->tipe_pesanan->label(),
            'catatan' => $this->catatan,
            'sumber' => $this->sumber->value,
            'status_pesanan' => $this->status_pesanan->value,
            'status_pesanan_label' => $this->status_pesanan->label(),
            'metode_pembayaran' => $this->metode_pembayaran->value,
            'metode_pembayaran_label' => $this->metode_pembayaran->label(),
            'status_pembayaran' => $this->status_pembayaran->value,
            'status_pembayaran_label' => $this->status_pembayaran->label(),
            'total' => $this->total,
            'uang_diterima' => $this->uang_diterima,
            'kembalian' => $this->kembalian,
            'waktu_lunas' => $this->waktu_lunas?->toIso8601String(),
            'items' => $this->whenLoaded('items', fn (): array => $this->items
                ->map(fn ($item): array => [
                    'menu_id' => $item->menu_id,
                    'nama_menu' => $item->nama_menu,
                    'harga_satuan' => $item->harga_satuan,
                    'jumlah' => $item->jumlah,
                    'subtotal' => $item->subtotal,
                    'catatan' => $item->catatan,
                ])
                ->all()),
        ];
    }
}

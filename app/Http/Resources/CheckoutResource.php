<?php

namespace App\Http\Resources;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Respons checkout QRIS Pelanggan B.
 *
 * Kontrak: docs/ROUTES.md §4.1. Hanya berisi data yang dibutuhkan halaman
 * pembayaran; tidak ada `server_key` atau data sensitif lain.
 *
 * @mixin Order
 */
class CheckoutResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Payment|null $percobaan */
        $percobaan = $this->payments->sortByDesc('id')->first();

        return [
            'kode_pesanan' => $this->kode_pesanan,
            'total' => $this->total,
            'status_pesanan' => $this->status_pesanan->value,
            'status_pembayaran' => $this->status_pembayaran->value,
            'batas_waktu_pembayaran' => $this->batas_waktu_pembayaran?->toIso8601String(),
            'snap_token' => $percobaan?->snap_token,
            'snap_redirect_url' => $percobaan?->snap_redirect_url,
        ];
    }
}

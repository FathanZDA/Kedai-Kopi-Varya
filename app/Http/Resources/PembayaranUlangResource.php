<?php

namespace App\Http\Resources;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Token Snap untuk percobaan pembayaran yang dibuka/dibuat ulang.
 *
 * Kontrak: docs/ROUTES.md §4.3.
 *
 * @mixin Payment
 */
class PembayaranUlangResource extends JsonResource
{
    public function __construct(Payment $resource, private readonly Order $order)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kode_pesanan' => $this->order->kode_pesanan,
            'snap_token' => $this->snap_token,
            'snap_redirect_url' => $this->snap_redirect_url,
            'batas_waktu_pembayaran' => $this->order->batas_waktu_pembayaran?->toIso8601String(),
        ];
    }
}

<?php

namespace App\Http\Resources;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Status ringkas pesanan QRIS untuk polling halaman konfirmasi.
 *
 * Kontrak: docs/ROUTES.md §4.2. Tidak ada data sensitif atau payload Midtrans.
 *
 * @mixin Order
 */
class PesananStatusResource extends JsonResource
{
    public function __construct(Order $resource, private readonly bool $dapatBayarUlang)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'kode_pesanan' => $this->kode_pesanan,
            'status_pembayaran' => $this->status_pembayaran->value,
            'status_pesanan' => $this->status_pesanan->value,
            'nomor_antrean' => $this->nomor_antrean !== null
                ? str_pad((string) $this->nomor_antrean, 3, '0', STR_PAD_LEFT)
                : null,
            'total' => $this->total,
            'waktu_lunas' => $this->waktu_lunas?->toIso8601String(),
            'batas_waktu_pembayaran' => $this->batas_waktu_pembayaran?->toIso8601String(),
            'dapat_bayar_ulang' => $this->dapatBayarUlang,
        ];
    }
}

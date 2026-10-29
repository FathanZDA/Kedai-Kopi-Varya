<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Satu percobaan pembayaran QRIS Midtrans.
 *
 * Satu order bisa punya beberapa baris `payments` (P1, P2, ...) untuk audit
 * percobaan. Percobaan lama tidak dihapus saat pelanggan membayar ulang.
 *
 * @see docs/DATABASE.md §4.6
 */
class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'midtrans_order_id',
        'transaction_id',
        'status_transaksi',
        'status_code',
        'fraud_status',
        'gross_amount',
        'payment_type',
        'snap_token',
        'snap_redirect_url',
        'raw_response',
        'waktu_dibuat',
        'waktu_diproses',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // Disimpan sebagai decimal(15,2) mengikuti Midtrans, bukan integer.
            'gross_amount' => 'decimal:2',
            'raw_response' => 'array',
            'waktu_dibuat' => 'datetime',
            'waktu_diproses' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

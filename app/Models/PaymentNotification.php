<?php

namespace App\Models;

use App\Enums\StatusPemrosesanNotifikasi;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Log payload notifikasi (webhook) Midtrans.
 *
 * Setiap pengiriman webhook dicatat apa adanya untuk audit teknis
 * (PRD §7.6 PG-11). Tabel ini tidak memakai `created_at`/`updated_at`;
 * waktu dicatat lewat `received_at` dan `processed_at`.
 *
 * @see docs/DATABASE.md §4.7
 */
class PaymentNotification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'midtrans_order_id',
        'order_id',
        'transaction_status',
        'status_code',
        'gross_amount',
        'signature_valid',
        'payload',
        'status_pemrosesan',
        'catatan',
        'received_at',
        'processed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gross_amount' => 'decimal:2',
            'signature_valid' => 'boolean',
            'payload' => 'array',
            'status_pemrosesan' => StatusPemrosesanNotifikasi::class,
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}

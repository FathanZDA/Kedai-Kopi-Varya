<?php

namespace App\Models;

use App\Enums\MetodePembayaran;
use App\Enums\StatusPembayaran;
use App\Enums\StatusPesanan;
use App\Enums\SumberPesanan;
use App\Enums\TipePesanan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'kode_pesanan',
        'nomor_antrean',
        'tanggal_antrean',
        'nama_pelanggan',
        'tipe_pesanan',
        'catatan',
        'sumber',
        'status_pesanan',
        'metode_pembayaran',
        'status_pembayaran',
        'total',
        'uang_diterima',
        'kembalian',
        'cashier_id',
        'waktu_lunas',
        'batas_waktu_pembayaran',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tipe_pesanan' => TipePesanan::class,
            'sumber' => SumberPesanan::class,
            'status_pesanan' => StatusPesanan::class,
            'metode_pembayaran' => MetodePembayaran::class,
            'status_pembayaran' => StatusPembayaran::class,
            'nomor_antrean' => 'integer',
            'tanggal_antrean' => 'date',
            'total' => 'integer',
            'uang_diterima' => 'integer',
            'kembalian' => 'integer',
            'waktu_lunas' => 'datetime',
            'batas_waktu_pembayaran' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Kasir pembuat order; null untuk order dari QR pelanggan.
     */
    public function kasir(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }
}

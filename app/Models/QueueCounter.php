<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Penghitung nomor antrean per hari.
 *
 * Satu baris per tanggal. Baris dikunci dengan lockForUpdate() saat nomor
 * diambil agar tidak ada nomor ganda pada transaksi bersamaan.
 */
class QueueCounter extends Model
{
    protected $fillable = [
        'tanggal',
        'nomor_terakhir',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nomor_terakhir' => 'integer',
        ];
    }
}

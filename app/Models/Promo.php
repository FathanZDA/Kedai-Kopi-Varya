<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    use HasFactory;

    protected $fillable = [
        'judul',
        'deskripsi',
        'gambar',
        'urutan',
        'aktif',
        'mulai',
        'berakhir',
    ];

    protected function casts(): array
    {
        return [
            'aktif' => 'boolean',
            'urutan' => 'integer',
            'mulai' => 'date',
            'berakhir' => 'date',
        ];
    }

    public function scopeActive(Builder $query): Builder
    {
        $hariIni = now('Asia/Jakarta')->toDateString();

        return $query
            ->where('aktif', true)
            ->where(function (Builder $query) use ($hariIni): void {
                $query->whereNull('mulai')->orWhereDate('mulai', '<=', $hariIni);
            })
            ->where(function (Builder $query) use ($hariIni): void {
                $query->whereNull('berakhir')->orWhereDate('berakhir', '>=', $hariIni);
            });
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('urutan')->orderBy('id');
    }
}

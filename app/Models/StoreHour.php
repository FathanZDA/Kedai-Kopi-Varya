<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreHour extends Model
{
    use HasFactory;

    protected $fillable = [
        'hari',
        'jam_buka',
        'jam_tutup',
        'tutup',
    ];

    protected function casts(): array
    {
        return [
            'hari' => 'integer',
            'tutup' => 'boolean',
        ];
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('hari');
    }

    public function getHariLabelAttribute(): string
    {
        return match ($this->hari) {
            0 => 'Minggu',
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
            6 => 'Sabtu',
            default => '-',
        };
    }

    public function getRentangAttribute(): string
    {
        if ($this->tutup || ! $this->jam_buka || ! $this->jam_tutup) {
            return 'Tutup';
        }

        return str_replace(':', '.', substr((string) $this->jam_buka, 0, 5))
            .' - '.str_replace(':', '.', substr((string) $this->jam_tutup, 0, 5));
    }
}

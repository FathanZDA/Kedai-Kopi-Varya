<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan toko. Selalu satu baris (id = 1); accessor `instance()`
 * supaya view tidak perlu menulis id di mana-mana.
 */
class StoreSetting extends Model
{
    protected $fillable = [
        'nama_toko',
        'tagline',
        'sejarah',
        'alamat',
        'maps_url',
        'maps_embed',
        'telepon',
        'whatsapp',
        'instagram',
        'fasilitas',
    ];

    protected function casts(): array
    {
        return [
            'fasilitas' => 'array',
        ];
    }

    public static function instance(): self
    {
        return static::first() ?? new static;
    }

    /** "08.00 - 23.00" — dibaca dari tabel store_hours. */
    public function getHoursSummaryAttribute(): string
    {
        $hours = StoreHour::query()->ordered()->get();

        if ($hours->isEmpty()) {
            return '08.00 - 23.00';
        }

        $rentang = $hours
            ->where('tutup', false)
            ->map(fn (StoreHour $hour): string => str_replace(':', '.', substr((string) $hour->jam_buka, 0, 5))
                .' - '.str_replace(':', '.', substr((string) $hour->jam_tutup, 0, 5)))
            ->unique()
            ->values();

        if ($rentang->isEmpty()) {
            return 'Tutup';
        }

        return $rentang->count() === 1
            ? $rentang->first()
            : 'Bervariasi per hari';
    }
}

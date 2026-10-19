<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan toko. Selalu satu baris (id = 1); accessor `instance()`
 * supaya view tidak perlu-stubbing id di mana-mana.
 */
class StoreSetting extends Model
{
    protected $fillable = [
        'name',
        'tagline',
        'address',
        'landmark',
        'maps_url',
        'instagram',
        'about_text',
        'hours',
    ];

    protected function casts(): array
    {
        return [
            'hours' => 'array',
        ];
    }

    public static function instance(): self
    {
        return static::first() ?? new static;
    }

    /** "08.00 - 23.00" — dibaca dari JSON jam operasional. */
    public function getHoursSummaryAttribute(): string
    {
        $hours = array_filter($this->hours ?? []);

        if ($hours === []) {
            return '08.00 - 23.00';
        }

        $values = array_unique(array_values($hours));

        // Kalau semua hari sama jamnya, cukup tampilkan satu rentang.
        return count($values) === 1
            ? reset($values)
            : 'Bervariasi per hari';
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Menu extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'category_id',
        'nama',
        'slug',
        'harga',
        'deskripsi',
        'foto',
        'tersedia',
        'urutan',
    ];

    protected function casts(): array
    {
        return [
            'harga' => 'integer',
            'tersedia' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function scopeAvailable($query)
    {
        return $query->where('tersedia', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('urutan')->orderBy('nama');
    }

    /** "Rp 22.000" — format Indonesia tanpa desimal. */
    public function getFormattedPriceAttribute(): string
    {
        return 'Rp '.number_format($this->harga, 0, ',', '.');
    }
}

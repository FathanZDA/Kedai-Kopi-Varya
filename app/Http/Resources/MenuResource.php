<?php

namespace App\Http\Resources;

use App\Models\Menu;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Ringkasan menu untuk respons kasir.
 *
 * Dipakai `PATCH /kasir/menu/{menu}/availability`; kontrak mengikuti
 * docs/ROUTES.md §4.8. Field domain memakai bahasa Indonesia.
 *
 * @mixin Menu
 */
class MenuResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama' => $this->nama,
            'slug' => $this->slug,
            'harga' => $this->harga,
            'tersedia' => $this->tersedia,
            'tersedia_label' => $this->tersedia ? 'Tersedia' : 'Habis',
        ];
    }
}

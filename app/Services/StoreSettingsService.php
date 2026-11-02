<?php

namespace App\Services;

use App\Models\StoreHour;
use App\Models\StoreSetting;
use Illuminate\Support\Facades\DB;

/**
 * Penyimpanan Informasi Toko (PRD AD-05, §8.9).
 *
 * Profil toko disimpan pada satu baris `store_settings` (id = 1) dan jam
 * operasional pada tujuh baris `store_hours` (hari 0–6). Keduanya ditulis
 * dalam satu transaksi agar halaman publik tidak pernah membaca campuran
 * data lama dan baru.
 */
class StoreSettingsService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function simpan(array $data): StoreSetting
    {
        return DB::transaction(function () use ($data): StoreSetting {
            $store = StoreSetting::query()->lockForUpdate()->first();

            if ($store === null) {
                $store = new StoreSetting;
                $store->id = 1;
            }

            $store->fill([
                'nama_toko' => $data['nama_toko'],
                'tagline' => $data['tagline'] ?? null,
                'sejarah' => $data['sejarah'] ?? null,
                'alamat' => $data['alamat'] ?? null,
                'maps_url' => $data['maps_url'] ?? null,
                'maps_embed' => $data['maps_embed'] ?? null,
                'telepon' => $data['telepon'] ?? null,
                'whatsapp' => $data['whatsapp'] ?? null,
                'instagram' => $data['instagram'] ?? null,
                'fasilitas' => $data['fasilitas'] ?? [],
            ]);
            $store->save();

            $this->simpanJam($data['jam'] ?? []);

            return $store->refresh();
        }, 3);
    }

    /**
     * @param  array<int, array{buka: ?string, tutup: ?string, libur: bool}>  $jam
     */
    private function simpanJam(array $jam): void
    {
        foreach (range(0, 6) as $hari) {
            $baris = $jam[$hari] ?? [];
            $libur = (bool) ($baris['libur'] ?? false);

            StoreHour::query()->updateOrCreate(
                ['hari' => $hari],
                [
                    'jam_buka' => $libur ? null : ($baris['buka'] ?? null),
                    'jam_tutup' => $libur ? null : ($baris['tutup'] ?? null),
                    'tutup' => $libur,
                ],
            );
        }
    }
}

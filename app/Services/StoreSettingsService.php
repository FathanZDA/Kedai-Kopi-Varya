<?php

namespace App\Services;

use App\Models\StoreHour;
use App\Models\StoreSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Penyimpanan Informasi Toko (PRD AD-05, §8.9).
 *
 * Profil toko disimpan pada satu baris `store_settings` (id = 1) dan jam
 * operasional pada tujuh baris `store_hours` (hari 0–6). Keduanya ditulis
 * dalam satu transaksi agar halaman publik tidak pernah membaca campuran
 * data lama dan baru.
 *
 * Foto Tentang Kami disimpan terpisah dari form profil karena butuh unggah
 * berkas (multipart), sementara `PUT /admin/toko` memakai JSON biasa.
 */
class StoreSettingsService
{
    private const DIREKTORI_FOTO = 'images/toko';

    private const PREFIKS_UNGGAHAN = 'unggahan-';

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
     * Simpan foto Tentang Kami dan hapus unggahan lama bila ada.
     */
    public function simpanFotoTentang(UploadedFile $file): StoreSetting
    {
        return DB::transaction(function () use ($file): StoreSetting {
            $store = $this->barisToko();
            $lama = $store->foto_tentang;

            $store->fill(['foto_tentang' => $this->simpanFoto($file)])->save();
            $this->hapusFotoUnggahan($lama);

            return $store->refresh();
        }, 3);
    }

    /**
     * Hapus foto Tentang Kami sehingga halaman publik kembali memakai
     * placeholder.
     */
    public function hapusFotoTentang(): StoreSetting
    {
        return DB::transaction(function (): StoreSetting {
            $store = $this->barisToko();
            $lama = $store->foto_tentang;

            $store->fill(['foto_tentang' => null])->save();
            $this->hapusFotoUnggahan($lama);

            return $store->refresh();
        }, 3);
    }

    private function barisToko(): StoreSetting
    {
        $store = StoreSetting::query()->lockForUpdate()->first();

        if ($store === null) {
            $store = new StoreSetting;
            $store->id = 1;
        }

        return $store;
    }

    private function simpanFoto(UploadedFile $file): string
    {
        $ekstensi = strtolower($file->getClientOriginalExtension() ?: (string) $file->extension());
        $nama = self::PREFIKS_UNGGAHAN.Str::random(24).($ekstensi !== '' ? '.'.$ekstensi : '');

        $file->move(public_path(self::DIREKTORI_FOTO), $nama);

        return self::DIREKTORI_FOTO.'/'.$nama;
    }

    /**
     * Hanya berkas unggahan yang boleh dihapus; berkas kanonik di `public/images`
     * tidak pernah disentuh.
     */
    private function hapusFotoUnggahan(?string $path): void
    {
        $prefiks = self::DIREKTORI_FOTO.'/'.self::PREFIKS_UNGGAHAN;

        if (! $path || ! str_starts_with($path, $prefiks)) {
            return;
        }

        $absolut = public_path($path);

        if (is_file($absolut)) {
            @unlink($absolut);
        }
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

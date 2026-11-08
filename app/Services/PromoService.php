<?php

namespace App\Services;

use App\Models\Promo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pengelolaan iklan & promosi oleh admin (PRD AD-04, §8.9).
 *
 * Gambar unggahan disimpan di `public/images/promos` dengan prefiks khusus
 * agar gambar kanonik hasil seeder (di `public/images/menus`) tidak pernah
 * terhapus. Promosi memakai hard delete karena tabel `promos` tidak memakai
 * soft delete.
 */
class PromoService
{
    private const DIREKTORI_GAMBAR = 'images/promos';

    private const PREFIKS_UNGGAHAN = 'unggahan-promo-';

    /**
     * @param  array<string, mixed>  $data
     */
    public function buat(array $data): Promo
    {
        return DB::transaction(function () use ($data): Promo {
            return Promo::create([
                'judul' => (string) $data['judul'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'gambar' => $this->simpanGambar($data['gambar'] ?? null),
                'urutan' => (int) $data['urutan'],
                'aktif' => (bool) $data['aktif'],
                'mulai' => $data['mulai'] ?? null,
                'berakhir' => $data['berakhir'] ?? null,
            ]);
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function ubah(Promo $promo, array $data): Promo
    {
        return DB::transaction(function () use ($promo, $data): Promo {
            $gambarBaru = $this->simpanGambar($data['gambar'] ?? null);

            if ($gambarBaru !== null) {
                $this->hapusGambarUnggahan($promo->gambar);
            }

            $promo->update([
                'judul' => (string) $data['judul'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'gambar' => $gambarBaru ?? $promo->gambar,
                'urutan' => (int) $data['urutan'],
                'aktif' => (bool) $data['aktif'],
                'mulai' => $data['mulai'] ?? null,
                'berakhir' => $data['berakhir'] ?? null,
            ]);

            return $promo;
        }, 3);
    }

    public function hapus(Promo $promo): void
    {
        DB::transaction(function () use ($promo): void {
            $this->hapusGambarUnggahan($promo->gambar);
            $promo->delete();
        }, 3);
    }

    public function aturStatus(Promo $promo, bool $aktif): Promo
    {
        $promo->update(['aktif' => $aktif]);

        return $promo;
    }

    private function simpanGambar(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $ekstensi = strtolower($file->getClientOriginalExtension() ?: (string) $file->extension());
        $nama = self::PREFIKS_UNGGAHAN.Str::random(24).($ekstensi !== '' ? '.'.$ekstensi : '');

        $tujuan = public_path(self::DIREKTORI_GAMBAR);

        if (! is_dir($tujuan)) {
            mkdir($tujuan, 0755, true);
        }

        $file->move($tujuan, $nama);

        return self::DIREKTORI_GAMBAR.'/'.$nama;
    }

    private function hapusGambarUnggahan(?string $path): void
    {
        $prefiks = self::DIREKTORI_GAMBAR.'/'.self::PREFIKS_UNGGAHAN;

        if (! $path || ! str_starts_with($path, $prefiks)) {
            return;
        }

        $absolut = public_path($path);

        if (is_file($absolut)) {
            @unlink($absolut);
        }
    }
}

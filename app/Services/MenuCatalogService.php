<?php

namespace App\Services;

use App\Models\Menu;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Pengelolaan katalog menu oleh admin (PRD AD-02, §8.6).
 *
 * Foto unggahan disimpan di `public/images/menus` dengan prefiks khusus agar
 * berkas kanonik hasil seeder tidak pernah terhapus. Penghapusan menu memakai
 * soft delete supaya snapshot `order_items` tetap utuh.
 */
class MenuCatalogService
{
    private const DIREKTORI_FOTO = 'images/menus';

    private const PREFIKS_UNGGAHAN = 'unggahan-';

    /**
     * @param  array<string, mixed>  $data
     */
    public function buat(array $data): Menu
    {
        return DB::transaction(function () use ($data): Menu {
            $foto = $this->simpanFoto($data['foto'] ?? null);

            return Menu::create([
                'category_id' => (int) $data['category_id'],
                'nama' => (string) $data['nama'],
                'slug' => $this->slugUnik((string) $data['nama']),
                'harga' => (int) $data['harga'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'foto' => $foto,
                'tersedia' => (bool) $data['tersedia'],
                'terlaris' => (bool) ($data['terlaris'] ?? false),
                'urutan' => ((int) Menu::withTrashed()->max('urutan')) + 1,
            ]);
        }, 3);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function ubah(Menu $menu, array $data): Menu
    {
        return DB::transaction(function () use ($menu, $data): Menu {
            $fotoBaru = $this->simpanFoto($data['foto'] ?? null);

            if ($fotoBaru !== null) {
                $this->hapusFotoUnggahan($menu->foto);
            }

            $menu->update([
                'category_id' => (int) $data['category_id'],
                'nama' => (string) $data['nama'],
                'harga' => (int) $data['harga'],
                'deskripsi' => $data['deskripsi'] ?? null,
                'foto' => $fotoBaru ?? $menu->foto,
                'tersedia' => (bool) $data['tersedia'],
                'terlaris' => (bool) ($data['terlaris'] ?? false),
            ]);

            return $menu;
        }, 3);
    }

    public function hapus(Menu $menu): void
    {
        // Soft delete: berkas foto dibiarkan agar menu masih bisa dipulihkan
        // dan riwayat pesanan lama tetap merujuk data yang sama.
        $menu->delete();
    }

    public function aturKetersediaan(Menu $menu, bool $tersedia): Menu
    {
        $menu->update(['tersedia' => $tersedia]);

        return $menu;
    }

    /** Penanda "Terlaris" manual dari admin (F-75); tidak mengubah ketersediaan. */
    public function aturTerlaris(Menu $menu, bool $terlaris): Menu
    {
        $menu->update(['terlaris' => $terlaris]);

        return $menu;
    }

    /**
     * Slug harus unik termasuk terhadap menu yang sudah di-soft-delete,
     * karena index unik database tetap menghitung baris `deleted_at`.
     */
    private function slugUnik(string $nama): string
    {
        $dasar = Str::slug($nama) ?: 'menu';
        $slug = $dasar;
        $nomor = 1;

        while (Menu::withTrashed()->where('slug', $slug)->exists()) {
            $nomor++;
            $slug = $dasar.'-'.$nomor;
        }

        return $slug;
    }

    private function simpanFoto(?UploadedFile $file): ?string
    {
        if (! $file) {
            return null;
        }

        $ekstensi = strtolower($file->getClientOriginalExtension() ?: (string) $file->extension());
        $nama = self::PREFIKS_UNGGAHAN.Str::random(24).($ekstensi !== '' ? '.'.$ekstensi : '');

        $file->move(public_path(self::DIREKTORI_FOTO), $nama);

        return self::DIREKTORI_FOTO.'/'.$nama;
    }

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
}

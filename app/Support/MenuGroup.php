<?php

namespace App\Support;

class MenuGroup
{
    public const KOPI = 'kopi';

    public const BUKAN_KOPI = 'bukan-kopi';

    public const MAKANAN_CAMILAN = 'makanan-camilan';

    public const LAINNYA = 'lainnya';

    /**
     * Kelompok filter publik. Kategori database final ada tiga baris
     * (Kopi, Bukan Kopi, Makanan & Camilan). Slug lama tetap diterima
     * sebagai alias agar halaman publik tidak rusak bila migrasi data
     * belum dijalankan.
     *
     * @return array<int, array{slug: string, label: string, categories: array<int, string>}>
     */
    public static function all(): array
    {
        return [
            [
                'slug' => self::KOPI,
                'label' => 'Kopi',
                'categories' => ['kopi'],
            ],
            [
                'slug' => self::BUKAN_KOPI,
                'label' => 'Bukan Kopi',
                'categories' => ['bukan-kopi', 'teh', 'autentik'],
            ],
            [
                'slug' => self::MAKANAN_CAMILAN,
                'label' => 'Makanan & Camilan',
                'categories' => ['makanan-camilan', 'makanan', 'cemilan'],
            ],
        ];
    }

    /** Kelompok untuk satu slug kategori; kategori tak dikenal hanya tampil di "Semua". */
    public static function forSlug(?string $slug): string
    {
        foreach (self::all() as $group) {
            if (in_array($slug, $group['categories'], true)) {
                return $group['slug'];
            }
        }

        return self::LAINNYA;
    }
}

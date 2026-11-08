<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Gabungkan lima kategori lama menjadi tiga kategori final.
     *
     * Data menu yang sudah ada dipindahkan sebelum kategori lama dihapus,
     * termasuk menu yang sudah di-soft-delete, agar riwayat tetap utuh.
     */
    public function up(): void
    {
        if (! Schema::hasTable('categories') || ! Schema::hasTable('menus')) {
            return;
        }

        $oldSlugs = ['teh', 'autentik', 'makanan', 'cemilan'];

        if (! DB::table('categories')->whereIn('slug', $oldSlugs)->exists()) {
            return;
        }

        DB::transaction(function () use ($oldSlugs): void {
            $now = now();

            $kopiId = DB::table('categories')->where('slug', 'kopi')->value('id')
                ?? DB::table('categories')->insertGetId([
                    'nama' => 'Kopi',
                    'slug' => 'kopi',
                    'urutan' => 1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

            $bukanKopiId = DB::table('categories')->where('slug', 'bukan-kopi')->value('id')
                ?? DB::table('categories')->insertGetId([
                    'nama' => 'Bukan Kopi',
                    'slug' => 'bukan-kopi',
                    'urutan' => 2,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

            $makananCamilanId = DB::table('categories')->where('slug', 'makanan-camilan')->value('id')
                ?? DB::table('categories')->insertGetId([
                    'nama' => 'Makanan & Camilan',
                    'slug' => 'makanan-camilan',
                    'urutan' => 3,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

            $bukanKopiLama = DB::table('categories')
                ->whereIn('slug', ['teh', 'autentik'])
                ->pluck('id');

            if ($bukanKopiLama->isNotEmpty()) {
                DB::table('menus')
                    ->whereIn('category_id', $bukanKopiLama)
                    ->update(['category_id' => $bukanKopiId]);
            }

            $makananCamilanLama = DB::table('categories')
                ->whereIn('slug', ['makanan', 'cemilan'])
                ->pluck('id');

            if ($makananCamilanLama->isNotEmpty()) {
                DB::table('menus')
                    ->whereIn('category_id', $makananCamilanLama)
                    ->update(['category_id' => $makananCamilanId]);
            }

            DB::table('categories')->whereIn('slug', $oldSlugs)->delete();

            DB::table('categories')->where('id', $kopiId)->update([
                'nama' => 'Kopi',
                'urutan' => 1,
                'updated_at' => $now,
            ]);

            DB::table('categories')->where('id', $bukanKopiId)->update([
                'nama' => 'Bukan Kopi',
                'urutan' => 2,
                'updated_at' => $now,
            ]);

            DB::table('categories')->where('id', $makananCamilanId)->update([
                'nama' => 'Makanan & Camilan',
                'urutan' => 3,
                'updated_at' => $now,
            ]);
        });
    }

    /**
     * Penggabungan kategori tidak dapat dibalik tanpa data asal per menu.
     */
    public function down(): void
    {
        //
    }
};

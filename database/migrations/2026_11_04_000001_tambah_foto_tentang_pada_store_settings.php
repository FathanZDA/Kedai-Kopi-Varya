<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Foto halaman Tentang Kami (PRD AD-05, panel "Foto Tentang Kami").
 *
 * Disimpan sebagai path relatif terhadap `public/`, sama seperti foto menu
 * dan gambar promosi. Null berarti halaman publik memakai placeholder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('foto_tentang')->nullable()->after('sejarah');
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn('foto_tentang');
        });
    }
};

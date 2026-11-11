<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambah penanda "Terlaris" yang diatur manual oleh admin (F-75).
     *
     * Kolom ini hanya label tampilan; ia tidak mengubah harga, ketersediaan,
     * maupun snapshot `order_items`. Perhitungan menu terlaris di dashboard
     * tetap berasal dari transaksi lunas.
     */
    public function up(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->boolean('terlaris')->default(false)->after('tersedia');
        });
    }

    public function down(): void
    {
        Schema::table('menus', function (Blueprint $table) {
            $table->dropColumn('terlaris');
        });
    }
};

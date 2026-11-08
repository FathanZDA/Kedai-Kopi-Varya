<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengaturan toko. Dipakai satu baris saja (id = 1) karena seluruh
     * konten halaman publik berasal dari sini dan dikelola admin nanti.
     */
    public function up(): void
    {
        Schema::create('store_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Kedai Kopi Varya');
            $table->string('tagline')->default('Tempat Nongkrong // Komunitas // Ruang Kreasi');
            $table->string('address')->nullable();
            $table->string('landmark')->nullable(); // "Dekat Jembaten Peny Gravity"
            $table->string('maps_url')->nullable();
            $table->string('instagram')->nullable();
            $table->text('about_text')->nullable();
            $table->json('hours')->nullable(); // { "senin": "08.00 - 23.00", ... }
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_settings');
    }
};

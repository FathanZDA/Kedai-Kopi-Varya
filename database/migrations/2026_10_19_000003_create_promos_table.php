<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promos', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // "KEDAI VARYA"
            $table->string('subtitle')->nullable(); // "TEMPAT NONGKRONG // KOMUNITAS"
            $table->text('description')->nullable();
            $table->string('image')->nullable(); // latar hero
            $table->string('badge_text')->nullable(); // "WAJIB COBA!"
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promos');
    }
};

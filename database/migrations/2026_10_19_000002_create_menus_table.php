<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedInteger('price'); // Rupiah penuh, tanpa titik
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->boolean('is_featured')->default(false); // badge "Terlaris"
            $table->boolean('is_available')->default(true); // false = badge "Stok Habis"
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['category_id', 'is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};

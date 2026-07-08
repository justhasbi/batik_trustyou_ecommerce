<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_sizes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('size'); // S, M, L, XL, XXL
            $table->unsignedInteger('stock')->default(0);
            // Rentang tinggi/berat untuk fitur rekomendasi ukuran di chatbot
            $table->unsignedSmallInteger('min_height')->nullable(); // cm
            $table->unsignedSmallInteger('max_height')->nullable();
            $table->unsignedSmallInteger('min_weight')->nullable(); // kg
            $table->unsignedSmallInteger('max_weight')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'size']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_sizes');
    }
};
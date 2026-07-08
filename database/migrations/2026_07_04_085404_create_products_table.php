<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 12, 2);
            // Stok global (opsional). Untuk pakaian, stok riil dikelola per ukuran
            // di tabel product_sizes. Kolom ini berguna untuk aksesoris tanpa ukuran.
            $table->unsignedInteger('stock')->default(0);
            $table->string('motif')->nullable();
            $table->enum('fabric_type', ['tulis', 'cap', 'print'])->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('order_number')->unique();

            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('shipping_cost', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Status pembayaran / pesanan
            $table->enum('status', ['pending', 'paid', 'processing', 'completed', 'cancelled'])
                ->default('pending');
            // Status pengiriman (yang diatur admin dari dashboard & ditanyakan pelanggan)
            $table->enum('shipping_status', ['not_shipped', 'packed', 'shipped', 'in_transit', 'delivered'])
                ->default('not_shipped');

            // Data pengiriman
            $table->string('recipient_name');
            $table->string('recipient_phone');
            $table->text('shipping_address');
            $table->string('courier')->nullable();          // JNE, J&T, dll
            $table->string('tracking_number')->nullable();  // nomor resi

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
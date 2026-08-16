<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Pembayaran (semua dummy untuk demo)
            $table->string('payment_method')->nullable()->after('total');   // qris, ewallet, bank_transfer, cod
            $table->string('payment_channel')->nullable()->after('payment_method'); // GoPay, BCA, dll
            $table->string('transaction_code')->nullable()->unique()->after('payment_channel'); // kode transaksi dummy
            $table->string('va_number')->nullable()->after('transaction_code');     // nomor Virtual Account (bank transfer)
            $table->text('qr_payload')->nullable()->after('va_number');             // isi/payload QR (qris & e-wallet)
            $table->timestamp('payment_expires_at')->nullable()->after('qr_payload');
            $table->timestamp('paid_at')->nullable()->after('payment_expires_at');

            // Pengiriman
            $table->string('shipping_method')->nullable()->after('courier');        // nama layanan, mis. "Reguler"
            $table->timestamp('shipped_at')->nullable()->after('tracking_number');
            $table->timestamp('delivered_at')->nullable()->after('shipped_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'payment_method',
                'payment_channel',
                'transaction_code',
                'va_number',
                'qr_payload',
                'payment_expires_at',
                'paid_at',
                'shipping_method',
                'shipped_at',
                'delivered_at',
            ]);
        });
    }
};

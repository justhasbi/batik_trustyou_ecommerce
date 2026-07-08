<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        $budi = User::where('email', 'budi@example.com')->first();
        $siti = User::where('email', 'siti@example.com')->first();
        if (! $budi || ! $siti) {
            return;
        }

        // Beragam status pengiriman agar demo dashboard & chatbot kaya
        $orders = [
            [
                'user_id' => $budi->id, 'order_number' => 'INV-DEMO0001',
                'subtotal' => 285000, 'shipping_cost' => 20000, 'total' => 305000,
                'status' => 'paid', 'shipping_status' => 'shipped',
                'recipient_name' => 'Budi Santoso', 'recipient_phone' => '081211112222',
                'shipping_address' => 'Jl. Melati No. 10, Bandung', 'courier' => 'JNE', 'tracking_number' => 'JNE1234567890',
            ],
            [
                'user_id' => $budi->id, 'order_number' => 'INV-DEMO0002',
                'subtotal' => 415000, 'shipping_cost' => 25000, 'total' => 440000,
                'status' => 'processing', 'shipping_status' => 'packed',
                'recipient_name' => 'Budi Santoso', 'recipient_phone' => '081211112222',
                'shipping_address' => 'Jl. Melati No. 10, Bandung', 'courier' => 'J&T', 'tracking_number' => null,
            ],
            [
                'user_id' => $siti->id, 'order_number' => 'INV-DEMO0003',
                'subtotal' => 265000, 'shipping_cost' => 18000, 'total' => 283000,
                'status' => 'completed', 'shipping_status' => 'delivered',
                'recipient_name' => 'Siti Rahayu', 'recipient_phone' => '081233334444',
                'shipping_address' => 'Jl. Anggrek No. 5, Yogyakarta', 'courier' => 'SiCepat', 'tracking_number' => 'SC0987654321',
            ],
        ];

        foreach ($orders as $o) {
            Order::updateOrCreate(['order_number' => $o['order_number']], $o);
        }
    }
}
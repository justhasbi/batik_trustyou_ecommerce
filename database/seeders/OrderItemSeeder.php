<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Seeder;

class OrderItemSeeder extends Seeder
{
    public function run(): void
    {
        // Item per pesanan (snapshot nama & harga saat checkout)
        $itemsByOrder = [
            'INV-DEMO0001' => [
                ['product_name' => 'Kemeja Batik Pria Parang Klasik', 'size' => 'L',  'price' => 285000, 'quantity' => 1],
            ],
            'INV-DEMO0002' => [
                ['product_name' => 'Dress Batik Wanita Sekar Jagad',  'size' => 'M',  'price' => 415000, 'quantity' => 1],
            ],
            'INV-DEMO0003' => [
                ['product_name' => 'Blus Batik Wanita Kawung',        'size' => 'S',  'price' => 265000, 'quantity' => 1],
            ],
        ];

        foreach ($itemsByOrder as $orderNumber => $items) {
            $order = Order::where('order_number', $orderNumber)->first();
            if (! $order) {
                continue;
            }
            foreach ($items as $item) {
                OrderItem::updateOrCreate(
                    ['order_id' => $order->id, 'product_name' => $item['product_name'], 'size' => $item['size']],
                    array_merge($item, [
                        'order_id' => $order->id,
                        'subtotal' => $item['price'] * $item['quantity'],
                    ])
                );
            }
        }
    }
}
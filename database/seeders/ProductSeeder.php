<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // slug kategori => id
        $cat = Category::pluck('id', 'slug');

        $products = [
            ['category' => 'batik-pria',   'name' => 'Kemeja Batik Pria Parang Klasik', 'price' => 285000, 'motif' => 'Parang',       'fabric_type' => 'cap',   'stock' => 0],
            ['category' => 'batik-pria',   'name' => 'Kemeja Batik Pria Mega Mendung',  'price' => 320000, 'motif' => 'Mega Mendung', 'fabric_type' => 'cap',   'stock' => 0],
            ['category' => 'batik-wanita', 'name' => 'Blus Batik Wanita Kawung',        'price' => 265000, 'motif' => 'Kawung',       'fabric_type' => 'print', 'stock' => 0],
            ['category' => 'batik-wanita', 'name' => 'Dress Batik Wanita Sekar Jagad',  'price' => 415000, 'motif' => 'Sekar Jagad',  'fabric_type' => 'tulis', 'stock' => 0],
            ['category' => 'batik-wanita', 'name' => 'Outer Batik Wanita Lereng',       'price' => 375000, 'motif' => 'Lereng',       'fabric_type' => 'cap',   'stock' => 0],
            ['category' => 'batik-anak',   'name' => 'Kemeja Batik Anak Truntum',       'price' => 145000, 'motif' => 'Truntum',      'fabric_type' => 'print', 'stock' => 0],
            ['category' => 'kain-batik',   'name' => 'Kain Batik Tulis Sidomukti',      'price' => 850000, 'motif' => 'Sidomukti',    'fabric_type' => 'tulis', 'stock' => 15],
            ['category' => 'aksesoris',    'name' => 'Selendang Batik Cap Sogan',       'price' => 125000, 'motif' => 'Sogan',        'fabric_type' => 'cap',   'stock' => 40],
        ];

        foreach ($products as $p) {
            Product::updateOrCreate(
                ['slug' => \Illuminate\Support\Str::slug($p['name'])],
                [
                    'category_id' => $cat[$p['category']] ?? null,
                    'name'        => $p['name'],
                    'description' => 'Batik ' . $p['motif'] . ' berkualitas dari Batik TrustYou. Bahan nyaman, jahitan rapi, cocok untuk acara formal maupun santai.',
                    'price'       => $p['price'],
                    'stock'       => $p['stock'],
                    'motif'       => $p['motif'],
                    'fabric_type' => $p['fabric_type'],
                    'is_active'   => true,
                ]
            );
        }
    }
}
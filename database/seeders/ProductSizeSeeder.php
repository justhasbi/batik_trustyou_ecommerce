<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductSize;
use Illuminate\Database\Seeder;

class ProductSizeSeeder extends Seeder
{
    public function run(): void
    {
        // Ukuran + rentang tinggi/berat (dipakai chatbot untuk rekomendasi ukuran).
        // Hanya untuk kategori pakaian; kain & aksesoris pakai stok produk langsung.
        $chart = [
            ['size' => 'S',  'stock' => 12, 'min_height' => 150, 'max_height' => 165, 'min_weight' => 45, 'max_weight' => 55],
            ['size' => 'M',  'stock' => 20, 'min_height' => 160, 'max_height' => 172, 'min_weight' => 55, 'max_weight' => 68],
            ['size' => 'L',  'stock' => 18, 'min_height' => 168, 'max_height' => 178, 'min_weight' => 68, 'max_weight' => 82],
            ['size' => 'XL', 'stock' => 10, 'min_height' => 175, 'max_height' => 185, 'min_weight' => 82, 'max_weight' => 95],
        ];

        $clothingCategories = ['batik-pria', 'batik-wanita', 'batik-anak'];

        $products = Product::with('category')->get()
            ->filter(fn ($p) => in_array($p->category?->slug, $clothingCategories));

        foreach ($products as $product) {
            foreach ($chart as $row) {
                ProductSize::updateOrCreate(
                    ['product_id' => $product->id, 'size' => $row['size']],
                    $row
                );
            }
        }
    }
}
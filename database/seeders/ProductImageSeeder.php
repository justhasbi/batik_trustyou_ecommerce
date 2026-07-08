<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Seeder;

class ProductImageSeeder extends Seeder
{
    public function run(): void
    {
        // Memakai URL placeholder agar gambar langsung tampil tanpa upload file.
        // Saat produksi, ganti dengan path hasil upload di storage (mis. 'products/xxx.jpg').
        foreach (Product::all() as $product) {
            for ($i = 1; $i <= 2; $i++) {
                ProductImage::updateOrCreate(
                    ['product_id' => $product->id, 'path' => "https://picsum.photos/seed/{$product->slug}-{$i}/600/600"],
                    ['is_primary' => $i === 1]
                );
            }
        }
    }
}
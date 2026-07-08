<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductSize;
use App\Models\User;
use Illuminate\Database\Seeder;

class CartItemSeeder extends Seeder
{
    public function run(): void
    {
        $andi = User::where('email', 'andi@example.com')->first();
        if (! $andi) {
            return;
        }
        $cart = Cart::where('user_id', $andi->id)->first();
        if (! $cart) {
            return;
        }

        // Contoh: 1 kemeja pria ukuran M + 1 selendang (tanpa ukuran)
        $kemeja = Product::where('slug', 'kemeja-batik-pria-mega-mendung')->first();
        $selendang = Product::where('slug', 'selendang-batik-cap-sogan')->first();

        if ($kemeja) {
            $sizeM = ProductSize::where('product_id', $kemeja->id)->where('size', 'M')->first();
            CartItem::updateOrCreate(
                ['cart_id' => $cart->id, 'product_id' => $kemeja->id, 'product_size_id' => $sizeM?->id],
                ['quantity' => 1]
            );
        }
        if ($selendang) {
            CartItem::updateOrCreate(
                ['cart_id' => $cart->id, 'product_id' => $selendang->id, 'product_size_id' => null],
                ['quantity' => 2]
            );
        }
    }
}
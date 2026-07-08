<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\User;
use Illuminate\Database\Seeder;

class CartSeeder extends Seeder
{
    public function run(): void
    {
        // Keranjang aktif milik pelanggan contoh (Andi)
        $andi = User::where('email', 'andi@example.com')->first();
        if ($andi) {
            Cart::updateOrCreate(['user_id' => $andi->id], []);
        }
    }
}
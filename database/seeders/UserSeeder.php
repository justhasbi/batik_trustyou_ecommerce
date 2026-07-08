<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {

        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('password123'),
            'is_admin' => true,
        ]);

        // Admin (bisa akses dashboard Filament)
        User::updateOrCreate(
            ['email' => 'admin@batiktrustyou.test'],
            [
                'name'     => 'Admin TrustYou',
                'phone'    => '081200000000',
                'is_admin' => true,
                'password' => Hash::make('password'),
            ]
        );

        // Pelanggan contoh
        $customers = [
            ['name' => 'Budi Santoso',   'email' => 'budi@example.com',   'phone' => '081211112222'],
            ['name' => 'Siti Rahayu',    'email' => 'siti@example.com',   'phone' => '081233334444'],
            ['name' => 'Andi Wijaya',    'email' => 'andi@example.com',   'phone' => '081255556666'],
        ];

        foreach ($customers as $c) {
            User::updateOrCreate(
                ['email' => $c['email']],
                [
                    'name'     => $c['name'],
                    'phone'    => $c['phone'],
                    'is_admin' => false,
                    'password' => Hash::make('password'),
                ]
            );
        }
    }
}
<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Batik Pria',   'slug' => 'batik-pria',   'description' => 'Koleksi kemeja dan atasan batik untuk pria.'],
            ['name' => 'Batik Wanita', 'slug' => 'batik-wanita', 'description' => 'Blus, dress, dan outer batik untuk wanita.'],
            ['name' => 'Batik Anak',   'slug' => 'batik-anak',   'description' => 'Batik nyaman untuk buah hati.'],
            ['name' => 'Kain Batik',   'slug' => 'kain-batik',   'description' => 'Kain batik tulis dan cap per meter.'],
            ['name' => 'Aksesoris',    'slug' => 'aksesoris',    'description' => 'Selendang, syal, dan pelengkap batik.'],
        ];

        foreach ($categories as $c) {
            Category::updateOrCreate(['slug' => $c['slug']], $c);
        }
    }
}
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('products')->insert([
            ['name' => 'Wireless Keyboard', 'price' => 49.99, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'USB-C Hub',          'price' => 34.95, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Mechanical Mouse',   'price' => 79.00, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Monitor Stand',      'price' => 29.50, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Webcam HD',          'price' => 59.99, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Desk Lamp',          'price' => 24.99, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}

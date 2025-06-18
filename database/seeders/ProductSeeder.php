<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'code' => 'PRD001',
                'category_id' => 1,
                'unit_id' => 3,
                'name' => 'Air Mineral Masoem Box',
                'description' => 'Air Mineral Masoem Box',
                'price' => 20000,
                'stock' => 100,
                'image' => null,
                'status' => 1,
            ],
            [
                'code' => 'PRD002',
                'category_id' => 1,
                'unit_id' => 2,
                'name' => 'Air Mineral Masoem Cup',
                'description' => 'Air Mineral Masoem Cup',
                'price' => 2000,
                'stock' => 100,
                'image' => null,
                'status' => 1,
            ]
        ];

        foreach ($products as $product) {
            \App\Models\Product::create($product);
        }
    }
}

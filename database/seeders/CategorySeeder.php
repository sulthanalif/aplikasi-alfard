<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'id' => 1,
                'name' => 'Cup',
                'slug' => 'cup',
                'description' => 'Cup',
                'status' => true
            ],
            [
                'id' => 2,
                'name' => 'Botol Wraping',
                'slug' => 'botol-wraping',
                'description' => 'Botol Kemasan Wraping',
                'status' => true
            ],
            [
                'id' => 3,
                'name' => 'Botol',
                'slug' => 'botol',
                'description' => 'Botol Kemasan',
                'status' => true
            ],
            [
                'id' => 4,
                'name' => 'Botol Jumbo',
                'slug' => 'botol-jumbo',
                'description' => 'Botol Jumbo',
                'status' => true
            ],
            [
                'id' => 5,
                'name' => 'Galon',
                'slug' => 'galon',
                'description' => 'Galon',
                'status' => true
            ],
            [
                'id' => 6,
                'name' => 'Galon Refill',
                'slug' => 'galon-refill',
                'description' => 'Galon Refill',
                'status' => true
            ],

        ];

        foreach ($categories as $category) {
            \App\Models\Category::create($category);
        }
    }
}

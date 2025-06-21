<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

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
                'category_id' => 1, // Cup
                'unit_id' => 1,
                'name' => 'Al-Masoem Cup 210 ml',
                'price' => 16000,
                'stock' => 100,
                 'purchase_price' => 14000,
               ],
              [
                'code' => 'PRD002',
                 'category_id' => 3, // Botol
                'unit_id' => 1,
                'name' => 'Al-Masoem Botol 330 ml',
                'price' => 26000,
                'stock' => 100,
                 'purchase_price' => 23000,
               ],
              [
                'code' => 'PRD003',
                 'category_id' => 2, // Botol Wraping
                'unit_id' => 1,
                'name' => 'Al-Masoem Botol 330 ml Kemasan Wraping',
                'price' => 25000,
                'stock' => 100,
                 'purchase_price' => 24000,
               ],
              [
                'code' => 'PRD004',
                 'category_id' => 3, // Botol
                'unit_id' => 1,
                'name' => 'Al-Masoem Botol 600 ml',
                'price' => 28500,
                'stock' => 100,
                 'purchase_price' => 25000,
               ],
              [
                'code' => 'PRD005',
                 'category_id' => 2, // Botol Wraping
                'unit_id' => 1,
                'name' => 'Al-Masoem Botol 600 ml Kemasan Wraping',
                'price' => 27500,
                'stock' => 100,
                 'purchase_price' => 25000,
               ],
              [
                'code' => 'PRD006',
                 'category_id' => 4, // Botol Jumbo
                'unit_id' => 1,
                'name' => 'Al-Masoem Botol Jumbo 1.500 ml',
                'price' => 28500,
                'stock' => 100,
                 'purchase_price' => 27000,
               ],
              [
                'code' => 'PRD007',
                 'category_id' => 6, // Galon Refill
                'unit_id' => 2, // Pcs
                'name' => 'Al-Masoem Galon refill 19 liter',
                'price' => 13000,
                'stock' => 100,
                'purchase_price' => 12000,
            ],
            [
                'code' => 'PRD008',
                'category_id' => 5, // Galon
                'unit_id' => 2, // Pcs
                'name' => 'Al-Masoem Galon 19 liter',
                'price' => 43000,
                'stock' => 100,
                 'purchase_price' => 20000,
               ],
              [
                'code' => 'PRD009',
                 'category_id' => 2, // Botol Wraping
                'unit_id' => 1,
                'name' => 'Al-Masoem Premium 220 ml Wraping',
                'price' => 22000,
                'stock' => 100,
                 'purchase_price' => 20000,
               ],
              [
                'code' => 'PRD010',
                 'category_id' => 3, // Botol
                'unit_id' => 1,
                'name' => 'Al-Masoem Premium 600 ml Dus',
                'price' => 30000,
                'stock' => 100,
                 'purchase_price' => 12000,
               ],
              [
                'code' => 'PRD011',
                 'category_id' => 1, // Cup
                'unit_id' => 1,
                'name' => 'Quazam Cup 200 ml',
                'price' => 13750,
                'stock' => 100,
                 'purchase_price' => 12000,
               ],
              [
                'code' => 'PRD012',
                 'category_id' => 3, // Botol
                'unit_id' => 1,
                'name' => 'Quazam Botol 600 ml',
                'price' => 24000,
                'stock' => 100,
                 'purchase_price' => 21000,
               ],
              [
                'code' => 'PRD013',
                 'category_id' => 2, // Botol Wraping
                'unit_id' => 1,
                'name' => 'Quazam Botol 600 ml Wraping',
                'price' => 23000,
                'stock' => 100,
                 'purchase_price' => 13000,
               ],
              [
                'code' => 'PRD014',
                 'category_id' => 1, // Cup
                'unit_id' => 1,
                'name' => 'Asri Cup 200 ml',
                'price' => 14500,
                'stock' => 100,
                 'purchase_price' => 13000,
               ],
              [
                'code' => 'PRD015',
                 'category_id' => 3, // Botol
                'unit_id' => 1,
                'name' => 'Asri Botol 600 ml',
                'price' => 24000,
                'stock' => 100,
                 'purchase_price' => 21000,
              ],
              [
                'code' => 'PRD016',
                 'category_id' => 2, // Botol Wraping
                'unit_id' => 1,
                'name' => 'Asri Botol 600 ml Wraping',
                'price' => 23000,
                'stock' => 100,
                 'purchase_price' => 22000,
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}

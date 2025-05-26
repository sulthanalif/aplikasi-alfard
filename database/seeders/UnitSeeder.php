<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            [
                'name' => 'Kg',
                'description' => 'Kilogram',
                'status' => true
            ],
            [
                'name' => 'Pcs',
                'description' => 'Pieces',
                'status' => true
            ],
            [
                'name' => 'Box',
                'description' => 'Box',
                'status' => true
            ]
        ];

        foreach ($units as $unit) {
            \App\Models\Unit::create($unit);
        }
    }
}

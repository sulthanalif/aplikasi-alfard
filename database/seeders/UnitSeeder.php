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
                'name' => 'Box',
                'description' => 'Box/Dus',
                'status' => true
            ],
            [
                'name' => 'Pcs',
                'description' => 'Pieces',
                'status' => true
            ],
        ];

        foreach ($units as $unit) {
            \App\Models\Unit::create($unit);
        }
    }
}

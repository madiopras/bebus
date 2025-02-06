<?php

namespace Database\Seeders;

use App\Models\SpecialDays;
use Illuminate\Database\Seeder;

class SpecialDaysSeeder extends Seeder
{
    public function run()
    {
        $specialDays = [
            [
                'name' => 'Tahun Baru 2025',
                'start_date' => '2025-01-01 00:00:00',
                'end_date' => '2025-01-01 23:59:59',
                'description' => 'Kenaikan harga tiket untuk Tahun Baru 2025',
                'price_percentage' => 150,
                'is_increase' => true,
                'is_active' => true,
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
            [
                'name' => 'Idul Fitri 2025',
                'start_date' => '2025-04-03 00:00:00',
                'end_date' => '2025-04-04 23:59:59',
                'description' => 'Kenaikan harga tiket untuk Idul Fitri 2025',
                'price_percentage' => 200,
                'is_increase' => true,
                'is_active' => true,
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
            [
                'name' => 'Natal 2024',
                'start_date' => '2024-12-25 00:00:00',
                'end_date' => '2024-12-25 23:59:59',
                'description' => 'Kenaikan harga tiket untuk Natal 2024',
                'price_percentage' => 150,
                'is_increase' => true,
                'is_active' => true,
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
        ];

        foreach ($specialDays as $specialDay) {
            SpecialDays::create($specialDay);
        }
    }
} 

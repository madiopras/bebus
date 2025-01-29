<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BusesTableSeeder extends Seeder
{
    public function run(): void
    {
        $buses = [
            [
                'id' => 1,
                'bus_number' => 'BUS-001',
                'type_bus' => 'MEDIUM',
                'bus_name' => 'Bus Ekonomi 1',
                'capacity' => 30,
                'cargo' => 100,
                'class_id' => 1,
                'description' => 'Bus ekonomi dengan kapasitas 30 penumpang',
                'is_active' => 1,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ],
            [
                'id' => 2,
                'bus_number' => 'BUS-002',
                'type_bus' => 'LARGE',
                'bus_name' => 'Bus Bisnis 1',
                'capacity' => 40,
                'cargo' => 150,
                'class_id' => 2,
                'description' => 'Bus bisnis dengan kapasitas 40 penumpang',
                'is_active' => 1,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ],
            [
                'id' => 3,
                'bus_number' => 'BUS-003',
                'type_bus' => 'LARGE',
                'bus_name' => 'Bus Eksekutif 1',
                'capacity' => 35,
                'cargo' => 120,
                'class_id' => 3,
                'description' => 'Bus eksekutif dengan kapasitas 35 penumpang',
                'is_active' => 1,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ]
        ];

        DB::table('buses')->insert($buses);
    }
} 
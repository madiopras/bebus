<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoutesTableSeeder extends Seeder
{
    public function run(): void
    {
        $routes = [
            [
                'id' => 1,
                'start_location_id' => 1,
                'end_location_id' => 2,
                'distance' => 350.5,
                'price' => 100000.00,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ],
            [
                'id' => 2,
                'start_location_id' => 1,
                'end_location_id' => 3,
                'distance' => 400.2,
                'price' => 120000.00,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ],
            [
                'id' => 3,
                'start_location_id' => 1,
                'end_location_id' => 4,
                'distance' => 280.8,
                'price' => 90000.00,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ],
            [
                'id' => 4,
                'start_location_id' => 2,
                'end_location_id' => 1,
                'distance' => 350.5,
                'price' => 100000.00,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ],
            [
                'id' => 5,
                'start_location_id' => 3,
                'end_location_id' => 1,
                'distance' => 400.2,
                'price' => 120000.00,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ],
            [
                'id' => 6,
                'start_location_id' => 4,
                'end_location_id' => 1,
                'distance' => 280.8,
                'price' => 90000.00,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ]
        ];

        DB::table('routes')->insert($routes);
    }
} 
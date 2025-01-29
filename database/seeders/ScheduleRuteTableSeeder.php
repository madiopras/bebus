<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ScheduleRuteTableSeeder extends Seeder
{
    public function run(): void
    {
        $scheduleRoutes = [
            [
                'id' => 1,
                'schedule_id' => 1,
                'route_id' => 1,
                'sequence_route' => 1,
                'departure_time' => '2025-01-15 08:00:00',
                'arrival_time' => '2025-01-15 14:00:00',
                'price_rute' => 100000.00,
                'description' => 'Rute Medan - Sibolga',
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45',
                'is_active' => 1
            ],
            [
                'id' => 2,
                'schedule_id' => 2,
                'route_id' => 2,
                'sequence_route' => 1,
                'departure_time' => '2025-01-15 09:00:00',
                'arrival_time' => '2025-01-15 15:00:00',
                'price_rute' => 120000.00,
                'description' => 'Rute Medan - Padang Sidempuan',
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45',
                'is_active' => 1
            ],
            [
                'id' => 3,
                'schedule_id' => 3,
                'route_id' => 3,
                'sequence_route' => 1,
                'departure_time' => '2025-01-15 10:00:00',
                'arrival_time' => '2025-01-15 16:00:00',
                'price_rute' => 90000.00,
                'description' => 'Rute Medan - Tarutung',
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45',
                'is_active' => 1
            ]
        ];

        DB::table('schedule_rute')->insert($scheduleRoutes);
    }
} 
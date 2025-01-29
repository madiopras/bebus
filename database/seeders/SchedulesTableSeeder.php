<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SchedulesTableSeeder extends Seeder
{
    public function run(): void
    {
        $schedules = [
            [
                'id' => 1,
                'bus_id' => 1,
                'departure_time' => '2025-01-15 08:00:00',
                'arrival_time' => '2025-01-15 14:00:00',
                'description' => 'Jadwal Bus Ekonomi 1',
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45',
                'location_id' => 1,
                'supir_id' => 2
            ],
            [
                'id' => 2,
                'bus_id' => 2,
                'departure_time' => '2025-01-15 09:00:00',
                'arrival_time' => '2025-01-15 15:00:00',
                'description' => 'Jadwal Bus Bisnis 1',
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45',
                'location_id' => 1,
                'supir_id' => 3
            ],
            [
                'id' => 3,
                'bus_id' => 3,
                'departure_time' => '2025-01-15 10:00:00',
                'arrival_time' => '2025-01-15 16:00:00',
                'description' => 'Jadwal Bus Eksekutif 1',
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45',
                'location_id' => 1,
                'supir_id' => 4
            ]
        ];

        DB::table('schedules')->insert($schedules);
    }
} 
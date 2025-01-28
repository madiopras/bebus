<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ScheduleSeatsTableSeeder extends Seeder
{
    public function run(): void
    {
        $scheduleSeats = [];
        
        // Schedule 1 - Bus 1 (30 seats)
        for ($i = 1; $i <= 30; $i++) {
            $scheduleSeats[] = [
                'id' => $i,
                'schedule_id' => 1,
                'seat_id' => $i,
                'is_available' => true,
                'description' => 'Kursi nomor ' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45',
                'booking_Id' => null,
                'schedule_rute_id' => null,
                'passengers_id' => null
            ];
        }
        
        // Schedule 2 - Bus 2 (40 seats)
        for ($i = 1; $i <= 40; $i++) {
            $scheduleSeats[] = [
                'id' => $i + 30,
                'schedule_id' => 2,
                'seat_id' => $i + 30,
                'is_available' => true,
                'description' => 'Kursi nomor ' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45',
                'booking_Id' => null,
                'schedule_rute_id' => null,
                'passengers_id' => null
            ];
        }
        
        // Schedule 3 - Bus 3 (35 seats)
        for ($i = 1; $i <= 35; $i++) {
            $scheduleSeats[] = [
                'id' => $i + 70,
                'schedule_id' => 3,
                'seat_id' => $i + 70,
                'is_available' => true,
                'description' => 'Kursi nomor ' . str_pad($i, 2, '0', STR_PAD_LEFT),
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45',
                'booking_Id' => null,
                'schedule_rute_id' => null,
                'passengers_id' => null
            ];
        }
        
        DB::table('scheduleseats')->insert($scheduleSeats);
    }
} 
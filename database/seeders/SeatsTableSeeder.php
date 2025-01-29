<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SeatsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $seats = [];
        
        // Bus 1 - 30 seats
        for ($i = 1; $i <= 30; $i++) {
            $seats[] = [
                'id' => $i,
                'bus_id' => 1,
                'seat_number' => str_pad($i, 2, '0', STR_PAD_LEFT),
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ];
        }
        
        // Bus 2 - 40 seats
        for ($i = 31; $i <= 70; $i++) {
            $seats[] = [
                'id' => $i,
                'bus_id' => 2,
                'seat_number' => str_pad($i - 30, 2, '0', STR_PAD_LEFT),
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ];
        }
        
        // Bus 3 - 35 seats
        for ($i = 71; $i <= 105; $i++) {
            $seats[] = [
                'id' => $i,
                'bus_id' => 3,
                'seat_number' => str_pad($i - 70, 2, '0', STR_PAD_LEFT),
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ];
        }

        DB::table('seats')->insert($seats);
    }
}

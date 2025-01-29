<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PassengersTableSeeder extends Seeder
{
    public function run(): void
    {
        $passengers = [
            [
                'id' => 1,
                'booking_id' => 1,
                'schedule_seat_id' => 1,
                'name' => 'John Doe',
                'phone_number' => '081234567891',
                'description' => 'Penumpang Bus Ekonomi 1',
                'created_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45',
                'updated_by_id' => 1
            ],
            [
                'id' => 2,
                'booking_id' => 1,
                'schedule_seat_id' => 2,
                'name' => 'Jane Doe',
                'phone_number' => '081234567892',
                'description' => 'Penumpang Bus Ekonomi 1',
                'created_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45',
                'updated_by_id' => 1
            ],
            [
                'id' => 3,
                'booking_id' => 2,
                'schedule_seat_id' => 31,
                'name' => 'Alice Smith',
                'phone_number' => '081234567893',
                'description' => 'Penumpang Bus Bisnis 1',
                'created_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45',
                'updated_by_id' => 1
            ],
            [
                'id' => 4,
                'booking_id' => 2,
                'schedule_seat_id' => 32,
                'name' => 'Bob Smith',
                'phone_number' => '081234567894',
                'description' => 'Penumpang Bus Bisnis 1',
                'created_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45',
                'updated_by_id' => 1
            ],
            [
                'id' => 5,
                'booking_id' => 3,
                'schedule_seat_id' => 71,
                'name' => 'Charlie Brown',
                'phone_number' => '081234567895',
                'description' => 'Penumpang Bus Eksekutif 1',
                'created_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45',
                'updated_by_id' => 1
            ],
            [
                'id' => 6,
                'booking_id' => 3,
                'schedule_seat_id' => 72,
                'name' => 'Diana Brown',
                'phone_number' => '081234567896',
                'description' => 'Penumpang Bus Eksekutif 1',
                'created_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45',
                'updated_by_id' => 1
            ]
        ];

        DB::table('passengers')->insert($passengers);
    }
} 
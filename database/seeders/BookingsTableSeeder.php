<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BookingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $bookings = [
            [
                'id' => 1,
                'user_id' => 1,
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'phone_number' => '081234567891',
                'schedule_id' => 1,
                'booking_date' => '2025-01-14 08:36:45',
                'payment_status' => 'PAID',
                'final_price' => 100000.00,
                'description' => 'Booking untuk Bus Ekonomi 1',
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45',
                'customer_type' => 'CUSTOMER',
                'payment_method' => 'MIDTRANS'
            ],
            [
                'id' => 2,
                'user_id' => 1,
                'name' => 'Jane Doe',
                'email' => 'jane@example.com',
                'phone_number' => '081234567892',
                'schedule_id' => 2,
                'booking_date' => '2025-01-14 08:36:45',
                'payment_status' => 'PAID',
                'final_price' => 120000.00,
                'description' => 'Booking untuk Bus Bisnis 1',
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45',
                'customer_type' => 'CUSTOMER',
                'payment_method' => 'MIDTRANS'
            ],
            [
                'id' => 3,
                'user_id' => 1,
                'name' => 'Bob Smith',
                'email' => 'bob@example.com',
                'phone_number' => '081234567893',
                'schedule_id' => 3,
                'booking_date' => '2025-01-14 08:36:45',
                'payment_status' => 'PAID',
                'final_price' => 90000.00,
                'description' => 'Booking untuk Bus Eksekutif 1',
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45',
                'customer_type' => 'CUSTOMER',
                'payment_method' => 'MIDTRANS'
            ]
        ];

        DB::table('bookings')->insert($bookings);
    }
} 
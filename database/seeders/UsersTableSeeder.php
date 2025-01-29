<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UsersTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'id' => 1,
                'name' => 'Admin Sumut',
                'email' => 'admin@sumatra.com',
                'phone_number' => '081234567890',
                'gender' => 'L',
                'password' => Hash::make('password'),
                'is_active' => 1,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'is_admin' => 0,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ],
            [
                'id' => 2,
                'name' => 'Supir Elit',
                'email' => 'supir@sumatra.com',
                'phone_number' => '085600121760',
                'gender' => 'pria',
                'password' => Hash::make('password'),
                'is_active' => 1,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'is_admin' => 0,
                'created_at' => '2025-01-14 08:43:37',
                'updated_at' => '2025-01-14 08:43:37'
            ],
            [
                'id' => 3,
                'name' => 'Supir Pemula',
                'email' => 'supir2@sumatra.com',
                'phone_number' => '085600121760',
                'gender' => 'pria',
                'password' => Hash::make('password'),
                'is_active' => 1,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'is_admin' => 0,
                'created_at' => '2025-01-14 11:26:18',
                'updated_at' => '2025-01-14 11:26:18'
            ],
            [
                'id' => 4,
                'name' => 'Supir Biasa Aja',
                'email' => 'supir3@sumatra.com',
                'phone_number' => '085600121760',
                'gender' => 'pria',
                'password' => Hash::make('password'),
                'is_active' => 1,
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'is_admin' => 0,
                'created_at' => '2025-01-14 11:26:43',
                'updated_at' => '2025-01-14 11:26:43'
            ]
        ];

        DB::table('users')->insert($users);
    }
}

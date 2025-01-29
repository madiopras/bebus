<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PermissionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            [
                'id' => 1,
                'name' => 'view-dashboard',
                'guard_name' => 'web',
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ],
            [
                'id' => 2,
                'name' => 'view-master',
                'guard_name' => 'web',
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ],
            [
                'id' => 3,
                'name' => 'view-transaction',
                'guard_name' => 'web',
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ],
            [
                'id' => 4,
                'name' => 'view-report',
                'guard_name' => 'web',
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ],
            [
                'id' => 5,
                'name' => 'view-setting',
                'guard_name' => 'web',
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ]
        ];

        DB::table('permissions')->insert($permissions);
    }
} 
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RolesTableSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'id' => 1,
                'name' => 'admin',
                'guard_name' => 'web',
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ],
            [
                'id' => 2, 
                'name' => 'supir',
                'guard_name' => 'web',
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ],
            [
                'id' => 3,
                'name' => 'customer',
                'guard_name' => 'web', 
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ]
        ];

        DB::table('roles')->insert($roles);
    }
} 
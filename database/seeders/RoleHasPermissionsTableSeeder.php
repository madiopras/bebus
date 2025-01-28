<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RoleHasPermissionsTableSeeder extends Seeder
{
    public function run(): void
    {
        $roleHasPermissions = [
            [
                'permission_id' => 1,
                'role_id' => 1
            ],
            [
                'permission_id' => 2,
                'role_id' => 1
            ],
            [
                'permission_id' => 3,
                'role_id' => 1
            ],
            [
                'permission_id' => 4,
                'role_id' => 1
            ],
            [
                'permission_id' => 5,
                'role_id' => 1
            ],
            [
                'permission_id' => 1,
                'role_id' => 2
            ]
        ];

        DB::table('role_has_permissions')->insert($roleHasPermissions);
    }
} 
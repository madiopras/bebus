<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Supir', 'guard_name' => 'api'],
            ['name' => 'Kasir Loket', 'guard_name' => 'api'],
            ['name' => 'Operator Bus', 'guard_name' => 'api'],
            ['name' => 'Customer Support', 'guard_name' => 'api'],
            ['name' => 'Manajer Keuangan', 'guard_name' => 'api'],
            ['name' => 'Scheduler', 'guard_name' => 'api'],
            ['name' => 'Marketing', 'guard_name' => 'api'],
            ['name' => 'IT', 'guard_name' => 'api'],
            ['name' => 'admin', 'guard_name' => 'sanctum'],
            ['name' => 'user', 'guard_name' => 'sanctum']
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}

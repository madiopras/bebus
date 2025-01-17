<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Supir', 'guard_name' => 'sanctum'],
            ['name' => 'Kasir Loket', 'guard_name' => 'sanctum'],
            ['name' => 'Operator Bus', 'guard_name' => 'sanctum'],
            ['name' => 'Customer Support', 'guard_name' => 'sanctum'],
            ['name' => 'Manajer Keuangan', 'guard_name' => 'sanctum'],
            ['name' => 'Scheduler', 'guard_name' => 'sanctum'],
            ['name' => 'Marketing', 'guard_name' => 'sanctum'],
            ['name' => 'IT', 'guard_name' => 'sanctum'],
            ['name' => 'admin', 'guard_name' => 'sanctum'],
            ['name' => 'user', 'guard_name' => 'sanctum']
        ];

        foreach ($roles as $role) {
            Role::create($role);
        }
    }
}

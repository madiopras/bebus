<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class AssignRoleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@sumatra.com')->first();
        
        if ($admin) {
            // Get roles with correct guard names
            $adminRole = Role::where('name', 'admin')->where('guard_name', 'sanctum')->first();
            $itRole = Role::where('name', 'IT')->where('guard_name', 'api')->first();
            $marketingRole = Role::where('name', 'Marketing')->where('guard_name', 'api')->first();
            
            // Assign roles
            if ($adminRole) $admin->assignRole($adminRole);
            if ($itRole) $admin->assignRole($itRole);
            if ($marketingRole) $admin->assignRole($marketingRole);
        }
    }
} 
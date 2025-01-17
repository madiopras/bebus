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
            $adminRole = Role::where('name', 'admin')->where('guard_name', 'api')->first();
            $itRole = Role::where('name', 'IT')->where('guard_name', 'api')->first();
            $marketingRole = Role::where('name', 'Marketing')->where('guard_name', 'api')->first();
            $operatorBusRole = Role::where('name', 'Operator Bus')->where('guard_name', 'api')->first();
            
            // Assign roles
            if ($adminRole) $admin->assignRole($adminRole);
            if ($itRole) $admin->assignRole($itRole);
            if ($marketingRole) $admin->assignRole($marketingRole);

            // Assign Operator Bus role to Operator Bus users
            if ($operatorBusRole) {
                $operatorBusUsers = User::whereIn('email', [
                    'ahmad.operator@bus.com', 
                    'budi.operator@bus.com', 
                    'chandra.operator@bus.com',
                    'handal.operator@bus.com',
                    'profesional.operator@bus.com',
                    'andal.operator@bus.com'
                ])->get();

                foreach ($operatorBusUsers as $user) {
                    $user->assignRole($operatorBusRole);
                }
            }
        }
    }
} 
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Admin default
        $admin = User::create([
            'name' => 'Admin Sumut',
            'email' => 'admin@sumatra.com',
            'password' => Hash::make('password'),
            'phone_number' => '081234567890',
            'gender' => 'L',
            'is_active' => 1,
            'created_by_id' => 1,
            'updated_by_id' => 1
        ]);
        $admin->assignRole('admin');

        // 3 Operator Bus
        $operatorBus1 = User::create([
            'name' => 'Supir Elit',
            'email' => 'ahmad.operator@bus.com',
            'password' => Hash::make('password'),
            'phone_number' => '081234567891',
            'gender' => 'L',
            'is_active' => 1,
            'created_by_id' => 1,
            'updated_by_id' => 1
        ]);
        $operatorBus1->assignRole('Operator Bus');

        $operatorBus2 = User::create([
            'name' => 'Supir Mahal',
            'email' => 'budi.operator@bus.com',
            'password' => Hash::make('password'),
            'phone_number' => '081234567892',
            'gender' => 'L',
            'is_active' => 1,
            'created_by_id' => 1,
            'updated_by_id' => 1
        ]);
        $operatorBus2->assignRole('Operator Bus');

        $operatorBus3 = User::create([
            'name' => 'Supir Jago',
            'email' => 'chandra.operator@bus.com',
            'password' => Hash::make('password'),
            'phone_number' => '081234567893',
            'gender' => 'L',
            'is_active' => 1,
            'created_by_id' => 1,
            'updated_by_id' => 1
        ]);
        $operatorBus3->assignRole('Operator Bus');

        // 3 Supir tambahan
        $operatorBus4 = User::create([
            'name' => 'Supir Handal',
            'email' => 'handal.operator@bus.com',
            'password' => Hash::make('password'),
            'phone_number' => '081234567894',
            'gender' => 'L',
            'is_active' => 1,
            'created_by_id' => 1,
            'updated_by_id' => 1
        ]);
        $operatorBus4->assignRole('Operator Bus');

        $operatorBus5 = User::create([
            'name' => 'Supir Profesional',
            'email' => 'profesional.operator@bus.com',
            'password' => Hash::make('password'),
            'phone_number' => '081234567895',
            'gender' => 'L',
            'is_active' => 1,
            'created_by_id' => 1,
            'updated_by_id' => 1
        ]);
        $operatorBus5->assignRole('Operator Bus');

        $operatorBus6 = User::create([
            'name' => 'Supir Andal',
            'email' => 'andal.operator@bus.com',
            'password' => Hash::make('password'),
            'phone_number' => '081234567896',
            'gender' => 'L',
            'is_active' => 1,
            'created_by_id' => 1,
            'updated_by_id' => 1
        ]);
        $operatorBus6->assignRole('Operator Bus');
    }
}

<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run()
    {
        User::create([
            'name' => 'Admin',
            'email' => 'admin@bebus.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'is_active' => true,
            'gender' => 'L',
            'phone_number' => '081234567890',
        ]);
    }
} 
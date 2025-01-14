<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin Sumut',
            'email' => 'admin@sumatra.com',
            'password' => Hash::make('password'),
            'phone_number' => '081234567890',
            'gender' => 'L',
            'is_active' => 1,
            'created_by_id' => 1,
            'updated_by_id' => 1
        ]);
    }
}

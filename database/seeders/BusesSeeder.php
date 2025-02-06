<?php

namespace Database\Seeders;

use App\Models\Buses;
use Illuminate\Database\Seeder;

class BusesSeeder extends Seeder
{
    public function run()
    {
        $buses = [
            [
                'bus_number' => 'BK 1234 AB',
                'bus_name' => 'Bus Ekonomi 01',
                'capacity' => 40,
                'class_id' => 1,
                'description' => 'Bus ekonomi dengan kapasitas 40 penumpang',
                'type_bus' => 'SINGLE DECKER',
                'cargo' => true,
                'is_active' => true,
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
            [
                'bus_number' => 'BK 5678 CD',
                'bus_name' => 'Bus Bisnis 01',
                'capacity' => 32,
                'class_id' => 2,
                'description' => 'Bus bisnis dengan kapasitas 32 penumpang',
                'type_bus' => 'SINGLE DECKER',
                'cargo' => true,
                'is_active' => true,
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
            [
                'bus_number' => 'BK 9012 EF',
                'bus_name' => 'Bus Eksekutif 01',
                'capacity' => 24,
                'class_id' => 3,
                'description' => 'Bus eksekutif dengan kapasitas 24 penumpang',
                'type_bus' => 'SINGLE DECKER',
                'cargo' => true,
                'is_active' => true,
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
        ];

        foreach ($buses as $bus) {
            Buses::create($bus);
        }
    }
} 

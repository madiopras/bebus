<?php

namespace Database\Seeders;

use App\Models\Buses;
use App\Models\Classes;
use App\Models\Facility;
use App\Models\Locations;
use App\Models\Routes;
use App\Models\SpecialDays;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class DefaultDataSeeder extends Seeder
{
    public function run(): void
    {
        // Default admin ID
        $adminId = 1;

        // Create Facilities
        $facilities = [
            ['name' => 'AC', 'description' => 'Air Conditioner'],
            ['name' => 'WiFi', 'description' => 'Free WiFi'],
            ['name' => 'Toilet', 'description' => 'Clean Toilet'],
            ['name' => 'Reclining Seat', 'description' => 'Comfortable Reclining Seats'],
            ['name' => 'USB Charging', 'description' => 'USB Charging Port'],
            ['name' => 'Selimut', 'description' => 'Selimut Gratis'],
            ['name' => 'Bantal', 'description' => 'Bantal Gratis'],
            ['name' => 'Snack', 'description' => 'Free Snack'],
            ['name' => 'Air Mineral', 'description' => 'Free Mineral Water']
        ];

        foreach ($facilities as $facility) {
            Facility::create([
                ...$facility,
                'created_by_id' => $adminId,
                'updated_by_id' => $adminId
            ]);
        }

        // Create Classes
        $classes = [
            ['class_name' => 'Executive', 'description' => 'Kelas Executive dengan fasilitas lengkap'],
            ['class_name' => 'Business', 'description' => 'Kelas Business dengan fasilitas memadai'],
            ['class_name' => 'Economy', 'description' => 'Kelas Economy dengan fasilitas standar']
        ];

        foreach ($classes as $class) {
            Classes::create([
                ...$class,
                'created_by_id' => $adminId,
                'updated_by_id' => $adminId
            ]);
        }

        // Assign Facilities to Classes
        $executiveClass = Classes::where('class_name', 'Executive')->first();
        $businessClass = Classes::where('class_name', 'Business')->first();
        $economyClass = Classes::where('class_name', 'Economy')->first();

        // Executive gets all facilities
        $executiveClass->facilities()->attach(
            Facility::all()->pluck('id')->toArray(),
            ['created_by_id' => $adminId, 'updated_by_id' => $adminId]
        );

        // Business gets some facilities
        $businessClass->facilities()->attach(
            Facility::whereIn('name', ['AC', 'WiFi', 'Reclining Seat', 'USB Charging', 'Selimut'])->pluck('id')->toArray(),
            ['created_by_id' => $adminId, 'updated_by_id' => $adminId]
        );

        // Economy gets basic facilities
        $economyClass->facilities()->attach(
            Facility::whereIn('name', ['AC', 'Reclining Seat'])->pluck('id')->toArray(),
            ['created_by_id' => $adminId, 'updated_by_id' => $adminId]
        );

        // Create Locations
        $locations = [
            ['name' => 'Medan', 'state' => 'Sumatera Utara', 'place' => 'Terminal Amplas', 'address' => 'Jl. Terminal Amplas No. 1'],
            ['name' => 'Pematang Siantar', 'state' => 'Sumatera Utara', 'place' => 'Terminal Siantar', 'address' => 'Jl. Terminal Siantar No. 1'],
            ['name' => 'Parapat', 'state' => 'Sumatera Utara', 'place' => 'Terminal Parapat', 'address' => 'Jl. Terminal Parapat No. 1'],
            ['name' => 'Padang Sidempuan', 'state' => 'Sumatera Utara', 'place' => 'Terminal Sidempuan', 'address' => 'Jl. Terminal Sidempuan No. 1'],
            ['name' => 'Sibolga', 'state' => 'Sumatera Utara', 'place' => 'Terminal Sibolga', 'address' => 'Jl. Terminal Sibolga No. 1']
        ];

        foreach ($locations as $location) {
            Locations::create([
                ...$location,
                'created_by_id' => $adminId,
                'updated_by_id' => $adminId
            ]);
        }

        // Create Routes
        $routesList = [
            ['start' => 'Medan', 'end' => 'Pematang Siantar', 'distance' => 128, 'price' => 50000],
            ['start' => 'Medan', 'end' => 'Parapat', 'distance' => 176, 'price' => 75000],
            ['start' => 'Medan', 'end' => 'Padang Sidempuan', 'distance' => 389, 'price' => 150000],
            ['start' => 'Medan', 'end' => 'Sibolga', 'distance' => 317, 'price' => 125000]
        ];

        foreach ($routesList as $route) {
            $startLocation = Locations::where('name', $route['start'])->first();
            $endLocation = Locations::where('name', $route['end'])->first();

            if ($startLocation && $endLocation) {
                Routes::create([
                    'start_location_id' => $startLocation->id,
                    'end_location_id' => $endLocation->id,
                    'distance' => $route['distance'],
                    'price' => $route['price'],
                    'created_by_id' => $adminId,
                    'updated_by_id' => $adminId
                ]);
            }
        }

        // Create Buses
        $buses = [
            [
                'bus_number' => 'BK 1234 XX',
                'type_bus' => 'Single Decker',
                'capacity' => 40,
                'bus_name' => 'Sumatra Express 1',
                'class_id' => $executiveClass->id,
                'description' => 'Bus Executive dengan fasilitas lengkap',
                'cargo' => true,
                'is_active' => true
            ],
            [
                'bus_number' => 'BK 5678 XX',
                'type_bus' => 'Single Decker',
                'capacity' => 45,
                'bus_name' => 'Sumatra Express 2',
                'class_id' => $businessClass->id,
                'description' => 'Bus Business dengan fasilitas memadai',
                'cargo' => true,
                'is_active' => true
            ],
            [
                'bus_number' => 'BK 9012 XX',
                'type_bus' => 'Single Decker',
                'capacity' => 50,
                'bus_name' => 'Sumatra Express 3',
                'class_id' => $economyClass->id,
                'description' => 'Bus Economy dengan fasilitas standar',
                'cargo' => false,
                'is_active' => true
            ]
        ];

        foreach ($buses as $bus) {
            Buses::create([
                ...$bus,
                'created_by_id' => $adminId,
                'updated_by_id' => $adminId
            ]);
        }

        // Create Special Days
        $specialDays = [
            [
                'name' => 'Lebaran 2024',
                'start_date' => '2024-04-10 00:00:00',
                'end_date' => '2024-04-15 23:59:59',
                'description' => 'Periode Lebaran 2024',
                'price_percentage' => 50,
                'is_increase' => true,
                'is_active' => true
            ],
            [
                'name' => 'Natal 2024',
                'start_date' => '2024-12-24 00:00:00',
                'end_date' => '2024-12-26 23:59:59',
                'description' => 'Periode Natal 2024',
                'price_percentage' => 25,
                'is_increase' => true,
                'is_active' => true
            ],
            [
                'name' => 'Tahun Baru 2025',
                'start_date' => '2024-12-31 00:00:00',
                'end_date' => '2025-01-02 23:59:59',
                'description' => 'Periode Tahun Baru 2025',
                'price_percentage' => 25,
                'is_increase' => true,
                'is_active' => true
            ]
        ];

        foreach ($specialDays as $specialDay) {
            SpecialDays::create([
                ...$specialDay,
                'created_by_id' => $adminId,
                'updated_by_id' => $adminId
            ]);
        }
    }
} 
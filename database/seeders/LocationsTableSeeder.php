<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LocationsTableSeeder extends Seeder
{
    public function run(): void
    {
        $locations = [
            [
                'id' => 1,
                'name' => 'Terminal Amplas',
                'address' => 'Jl. Sisingamangaraja, Amplas, Medan',
                'state' => 'Sumatera Utara',
                'place' => 'Medan',
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ],
            [
                'id' => 2,
                'name' => 'Terminal Sibolga',
                'address' => 'Jl. Terminal, Sibolga',
                'state' => 'Sumatera Utara',
                'place' => 'Sibolga',
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ],
            [
                'id' => 3,
                'name' => 'Terminal Padang Sidempuan',
                'address' => 'Jl. Terminal, Padang Sidempuan',
                'state' => 'Sumatera Utara',
                'place' => 'Padang Sidempuan',
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ],
            [
                'id' => 4,
                'name' => 'Terminal Tarutung',
                'address' => 'Jl. Terminal, Tarutung',
                'state' => 'Sumatera Utara',
                'place' => 'Tarutung',
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => '2025-01-14 08:36:45',
                'updated_at' => '2025-01-14 08:36:45'
            ]
        ];

        DB::table('locations')->insert($locations);
    }
} 
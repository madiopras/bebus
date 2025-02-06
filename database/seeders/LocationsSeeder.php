<?php

namespace Database\Seeders;

use App\Models\Locations;
use Illuminate\Database\Seeder;

class LocationsSeeder extends Seeder
{
    public function run()
    {
        $locations = [
            [
                'name' => 'Terminal Amplas Medan',
                'address' => 'Jl. Sisingamangaraja, Medan',
                'state' => 'Sumatera Utara',
                'place' => 'Medan',
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
            [
                'name' => 'Terminal Sibolga',
                'address' => 'Jl. DI Panjaitan, Sibolga',
                'state' => 'Sumatera Utara',
                'place' => 'Sibolga',
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
            [
                'name' => 'Terminal Padang Sidempuan',
                'address' => 'Jl. Sudirman, Padang Sidempuan',
                'state' => 'Sumatera Utara',
                'place' => 'Padang Sidempuan',
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
        ];

        foreach ($locations as $location) {
            Locations::create($location);
        }
    }
} 
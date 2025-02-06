<?php

namespace Database\Seeders;

use App\Models\Facility;
use Illuminate\Database\Seeder;

class FacilitiesSeeder extends Seeder
{
    public function run()
    {
        $facilities = [
            [
                'name' => 'AC',
                'description' => 'Air Conditioner',
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
            [
                'name' => 'Toilet',
                'description' => 'Toilet',
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
            [
                'name' => 'TV',
                'description' => 'Televisi',
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
            [
                'name' => 'Music',
                'description' => 'Sistem Audio',
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
            [
                'name' => 'Air Mineral',
                'description' => 'Air Mineral Gratis',
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
            [
                'name' => 'WiFi',
                'description' => 'Koneksi Internet',
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
            [
                'name' => 'Snack',
                'description' => 'Makanan Ringan',
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
        ];

        foreach ($facilities as $facility) {
            Facility::create($facility);
        }
    }
} 
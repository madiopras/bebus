<?php

namespace Database\Seeders;

use App\Models\ClassFacility;
use Illuminate\Database\Seeder;

class ClassFacilitiesSeeder extends Seeder
{
    public function run()
    {
        // Fasilitas untuk kelas Ekonomi (id: 1)
        $ekonomiFacilities = [1, 2, 4, 5]; // AC, Toilet, Music, Air Mineral
        foreach ($ekonomiFacilities as $facilityId) {
            ClassFacility::create([
                'class_id' => 1,
                'facility_id' => $facilityId,
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ]);
        }

        // Fasilitas untuk kelas Bisnis (id: 2)
        $bisnisFacilities = [1, 2, 3, 4, 5, 6]; // AC, Toilet, TV, Music, Air Mineral, WiFi
        foreach ($bisnisFacilities as $facilityId) {
            ClassFacility::create([
                'class_id' => 2,
                'facility_id' => $facilityId,
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ]);
        }

        // Fasilitas untuk kelas Eksekutif (id: 3)
        $eksekutifFacilities = [1, 2, 3, 4, 5, 6, 7]; // Semua fasilitas
        foreach ($eksekutifFacilities as $facilityId) {
            ClassFacility::create([
                'class_id' => 3,
                'facility_id' => $facilityId,
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ]);
        }
    }
} 
<?php

namespace Database\Seeders;

use App\Models\Classes;
use Illuminate\Database\Seeder;

class ClassesSeeder extends Seeder
{
    public function run()
    {
        $classes = [
            [
                'class_name' => 'EKONOMI',
                'description' => 'Kelas ekonomi dengan fasilitas standar',
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
            [
                'class_name' => 'BISNIS',
                'description' => 'Kelas bisnis dengan fasilitas lebih nyaman',
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
            [
                'class_name' => 'EKSEKUTIF',
                'description' => 'Kelas eksekutif dengan fasilitas premium',
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
        ];

        foreach ($classes as $class) {
            Classes::create($class);
        }
    }
} 

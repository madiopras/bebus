<?php

namespace Database\Seeders;

use App\Models\Routes;
use Illuminate\Database\Seeder;

class RoutesSeeder extends Seeder
{
    public function run()
    {
        $routes = [
            [
                'start_location_id' => 1,
                'end_location_id' => 2,
                'distance' => 350,
                'price' => 150000,
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
            [
                'start_location_id' => 2,
                'end_location_id' => 3,
                'distance' => 150,
                'price' => 100000,
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
            [
                'start_location_id' => 1,
                'end_location_id' => 3,
                'distance' => 400,
                'price' => 200000,
                'created_by_id' => 1,
                'updated_by_id' => 1,
            ],
        ];

        foreach ($routes as $route) {
            Routes::create($route);
        }
    }
} 
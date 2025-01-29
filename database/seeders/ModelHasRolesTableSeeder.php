<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ModelHasRolesTableSeeder extends Seeder
{
    public function run(): void
    {
        $modelHasRoles = [
            [
                'role_id' => 1,
                'model_type' => 'App\\Models\\User',
                'model_id' => 1
            ],
            [
                'role_id' => 2,
                'model_type' => 'App\\Models\\User',
                'model_id' => 2
            ],
            [
                'role_id' => 2,
                'model_type' => 'App\\Models\\User',
                'model_id' => 3
            ],
            [
                'role_id' => 2,
                'model_type' => 'App\\Models\\User',
                'model_id' => 4
            ]
        ];

        DB::table('model_has_roles')->insert($modelHasRoles);
    }
} 
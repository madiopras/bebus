<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        
        // Hapus data yang ada
        Permission::truncate();

        // Data permission
        $permissions = [
            ['name' => 'create_user', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:15:09'],
            ['name' => 'read_user', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:16:02'],
            ['name' => 'update_user', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:16:29'],
            ['name' => 'delete_user', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:16:54'],
            ['name' => 'create_location', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:32:17'],
            ['name' => 'read_location', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:32:36'],
            ['name' => 'update_location', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:41:01'],
            ['name' => 'delete_location', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:41:25'],
            ['name' => 'create_class', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:41:25'],
            ['name' => 'read_class', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:46:28'],
            ['name' => 'update_class', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:46:40'],
            ['name' => 'delete_class', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:47:03'],
            ['name' => 'create_route', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:47:43'],
            ['name' => 'read_route', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:47:58'],
            ['name' => 'update_route', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:48:17'],
            ['name' => 'delete_route', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:48:31'],
            ['name' => 'create_bus', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:49:14'],
            ['name' => 'read_bus', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:49:32'],
            ['name' => 'update_bus', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:49:50'],
            ['name' => 'delete_bus', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:50:13'],
            ['name' => 'create_specialdays', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:58:20'],
            ['name' => 'read_specialdays', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:58:44'],
            ['name' => 'update_specialdays', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:59:01'],
            ['name' => 'delete_specialdays', 'guard_name' => 'api', 'created_at' => '2024-12-29 05:59:45'],
            ['name' => 'create_schedule', 'guard_name' => 'api', 'created_at' => '2024-12-29 06:41:08'],
            ['name' => 'read_schedule', 'guard_name' => 'api', 'created_at' => '2024-12-29 06:41:31'],
            ['name' => 'update_schedule', 'guard_name' => 'api', 'created_at' => '2024-12-29 06:41:45'],
            ['name' => 'delete_schedule', 'guard_name' => 'api', 'created_at' => '2024-12-29 06:42:00'],
            ['name' => 'create_booking', 'guard_name' => 'api', 'created_at' => '2024-12-29 06:43:23'],
            ['name' => 'read_booking', 'guard_name' => 'api', 'created_at' => '2024-12-29 06:43:38'],
            ['name' => 'update_booking', 'guard_name' => 'api', 'created_at' => '2024-12-29 06:43:54'],
            ['name' => 'delete_booking', 'guard_name' => 'api', 'created_at' => '2024-12-29 06:44:27'],
            ['name' => 'read_seat', 'guard_name' => 'api', 'created_at' => '2024-12-29 06:45:47'],
            ['name' => 'update_seat', 'guard_name' => 'api', 'created_at' => '2024-12-29 06:46:10'],
            ['name' => 'read_scherute', 'guard_name' => 'api', 'created_at' => '2024-12-29 06:49:32'],
            ['name' => 'update_scherute', 'guard_name' => 'api', 'created_at' => '2024-12-29 06:49:48']
        ];

        // Insert data
        foreach ($permissions as $permission) {
            Permission::create($permission);
        }

        Schema::enableForeignKeyConstraints();
    }
}

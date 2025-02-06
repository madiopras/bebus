<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            UsersTableSeeder::class,
            RolesTableSeeder::class,
            PermissionsTableSeeder::class,
            ModelHasRolesTableSeeder::class,
            RoleHasPermissionsTableSeeder::class,
            BusesTableSeeder::class,
            LocationsTableSeeder::class,
            RoutesTableSeeder::class,
            SeatsTableSeeder::class,
            SchedulesTableSeeder::class,
            ScheduleRuteTableSeeder::class,
            ScheduleSeatsTableSeeder::class,
            BookingsTableSeeder::class,
            PassengersTableSeeder::class,
            ClassesSeeder::class,
            LocationsSeeder::class,
            RoutesSeeder::class,
            BusesSeeder::class,
            SpecialDaysSeeder::class,
        ]);
    }
}

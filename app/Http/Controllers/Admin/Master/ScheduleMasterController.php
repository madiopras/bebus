<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\Locations;
use App\Models\Buses;
use App\Models\User;
use App\Models\Routes;
use App\Models\SpecialDays;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleMasterController extends Controller
{
    public function index()
    {
        try {
            // Get current date minus 3 days
            $dateFilter = now()->today()->format('Y-m-d');

            // Get Locations
            $locations = Locations::select('id', 'name')->get();

            // Get Buses with schedules
            $buses = Buses::select(
                'buses.id',
                'buses.bus_name',
                'schedules.departure_time',
                'schedules.arrival_time',
                'classes.class_name'
            )
            ->leftJoin('schedules', 'buses.id', '=', 'schedules.bus_id')
            ->leftJoin('classes', 'buses.class_id', '=', 'classes.id')
            ->where('buses.is_active', 1)
            ->where(function($query) use ($dateFilter) {
                $query->whereNull('schedules.departure_time')
                      ->orWhere('schedules.arrival_time', '>', $dateFilter);
            })
            ->get();

            // Get Drivers (Operator Bus)
            $drivers = User::select(
                'users.id',
                'users.name',
                'schedules.departure_time',
                'schedules.arrival_time'
            )
            ->leftJoin('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->leftJoin('schedules', 'users.id', '=', 'schedules.supir_id')
            ->where('roles.name', '=', 'Operator Bus')
            ->where('users.is_active', 1)
            ->where(function($query) use ($dateFilter) {
                $query->whereNull('schedules.departure_time')
                      ->whereNull('schedules.arrival_time')
                      ->orWhere('schedules.arrival_time', '>', $dateFilter);
            })
            ->get();

            // Get Routes
            $routes = Routes::all();

            // Get Special Days with start_date greater than today
            $specialDays = SpecialDays::where('start_date', '>', now())->get();

            // Get Facilities
            $facilities = DB::table('facilities')
                ->select('id', 'name')
                ->get();

            return response()->json([
                'status' => true,
                'data' => [
                    'locations' => $locations,
                    'buses' => $buses,
                    'drivers' => $drivers,
                    'routes' => $routes,
                    'special_days' => $specialDays,
                    'facilities' => $facilities
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch master data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update()
    {
        try {
            // Get Locations
            $locations = Locations::select('id', 'name','place')->get();

            // Get All Buses with schedules without date filter
            $buses = Buses::select(
                'buses.id',
                'buses.bus_name',
                'schedules.departure_time',
                'schedules.arrival_time'
            )
            ->leftJoin('schedules', 'buses.id', '=', 'schedules.bus_id')
            ->where('buses.is_active', 1)
            ->get();

            // Get All Drivers (Operator Bus) without date filter
            $drivers = User::select(
                'users.id',
                'users.name',
                'schedules.departure_time',
                'schedules.arrival_time'
            )
            ->leftJoin('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->leftJoin('schedules', 'users.id', '=', 'schedules.supir_id')
            ->where('roles.name', '=', 'Operator Bus')
            ->where('users.is_active', 1)
            ->get();

            // Get Routes
            $routes = Routes::all();

            // Get All Special Days
            $specialDays = SpecialDays::all();

            return response()->json([
                'status' => true,
                'data' => [
                    'locations' => $locations,
                    'buses' => $buses,
                    'drivers' => $drivers,
                    'routes' => $routes,
                    'special_days' => $specialDays
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch master data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
} 
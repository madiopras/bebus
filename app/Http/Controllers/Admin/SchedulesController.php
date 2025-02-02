<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedules\StoreSchedulesRequest;
use App\Http\Requests\Schedules\UpdateSchedulesRequest;
use App\Models\Schedules;
use App\Models\ScheduleRute;
use App\Models\Locations;
use App\Models\Buses;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SchedulesController extends Controller
{
    private function getFormData()
    {
        // Get Locations
        $locations = Locations::select('id', 'name')->get();

        // Get current date minus 3 days
        $dateFilter = now()->subDays(3)->format('Y-m-d');

        // Get Buses with schedules
        $buses = Buses::select(
            'buses.id',
            'buses.bus_name',
            'schedules.departure_time',
            'schedules.arrival_time'
        )
        ->leftJoin('schedules', 'buses.id', '=', 'schedules.bus_id')
        ->where(function($query) use ($dateFilter) {
            $query->whereNull('schedules.departure_time')
                  ->orWhere('schedules.departure_time', '>', $dateFilter);
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
        ->where(function($query) use ($dateFilter) {
            $query->whereNull('schedules.departure_time')
                  ->orWhere('schedules.departure_time', '>', $dateFilter);
        })
        ->get();

        return [
            'locations' => $locations,
            'buses' => $buses,
            'drivers' => $drivers
        ];
    }

    public function create()
    {
        try {
            $formData = $this->getFormData();
            
            return response()->json([
                'status' => true,
                'data' => $formData
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch form data', 'error' => $e->getMessage()], 500);
        }
    }

    public function edit($id)
    {
        try {
            $schedule = Schedules::findOrFail($id);
            $formData = $this->getFormData();
            
            return response()->json([
                'status' => true,
                'data' => [
                    'schedule' => $schedule,
                    'form_data' => $formData
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch schedule data', 'error' => $e->getMessage()], 500);
        }
    }

    public function index(Request $request)
    {
        try {
            $filters = $request->only(['name', 'departure_time', 'bus_number', 'class_name', 'type_bus', 'nama_bus', 'is_active', 'tanggal_dari', 'tanggal_sampai', 'supir_name']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $query = Schedules::select(
                'schedules.id',
                'locations.name as lokasi_schedule',
                DB::raw("DATE_FORMAT(schedules.departure_time, '%d %M %Y') as tanggal_berangkat"),
                DB::raw("DATE_FORMAT(schedules.departure_time, '%H:%i') as jam"),
                'buses.bus_number',
                'buses.bus_name as nama_bus',
                'buses.type_bus',
                'users.name as supir_name'
            )
            ->leftJoin('locations', 'schedules.location_id', '=', 'locations.id')
            ->leftJoin('buses', 'schedules.bus_id', '=', 'buses.id')
            ->leftJoin('classes', 'buses.class_id', '=', 'classes.id')
            ->leftJoin('users', 'schedules.supir_id', '=', 'users.id');

            // Apply filters
            if (isset($filters['name'])) {
                $query->where('locations.name', 'like', '%' . $filters['name'] . '%');
            }
            if (isset($filters['departure_time'])) {
                $query->whereDate('schedules.departure_time', $filters['departure_time']);
            }
            if (isset($filters['tanggal_dari']) && isset($filters['tanggal_sampai'])) {
                $query->whereDate('schedules.departure_time', '>=', $filters['tanggal_dari'])
                      ->whereDate('schedules.departure_time', '<=', $filters['tanggal_sampai']);
            } else if (isset($filters['tanggal_dari'])) {
                $query->whereDate('schedules.departure_time', '>=', $filters['tanggal_dari']);
            } else if (isset($filters['tanggal_sampai'])) {
                $query->whereDate('schedules.departure_time', '<=', $filters['tanggal_sampai']);
            }
            if (isset($filters['bus_number'])) {
                $query->where('buses.bus_number', 'like', '%' . $filters['bus_number'] . '%');
            }
            if (isset($filters['nama_bus'])) {
                $query->where('buses.bus_name', 'like', '%' . $filters['nama_bus'] . '%');
            }
            if (isset($filters['type_bus'])) {
                $query->where('buses.type_bus', 'like', '%' . $filters['type_bus'] . '%');
            }
            if (isset($filters['class_name'])) {
                $query->where('classes.name', 'like', '%' . $filters['class_name'] . '%');
            }
            if (isset($filters['is_active'])) {
                $query->where('schedules.is_active', $filters['is_active']);
            }
            if (isset($filters['supir_name'])) {
                $query->where('users.name', 'like', '%' . $filters['supir_name'] . '%');
            }

            $schedules = $query->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'data' => $schedules->items(),
                'current_page' => $schedules->currentPage(),
                'total_pages' => $schedules->lastPage(),
                'total_items' => $schedules->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch schedules', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $schedule = Schedules::with('scheduleRutes')->findOrFail($id);

            return response()->json($schedule, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch schedule', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreSchedulesRequest $request)
    {
        try {
            // Validasi input wajib
            if (!$request->bus_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bus harus dipilih'
                ], 422);
            }

            if (!$request->supir_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Supir harus dipilih'
                ], 422);
            }

            if (!$request->location_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Lokasi harus dipilih'
                ], 422);
            }

            // Validasi minimal satu rute
            if (!$request->schedule_rutes || count($request->schedule_rutes) < 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Minimal harus mengisi satu rute'
                ], 422);
            }

            // Validasi waktu keberangkatan yang berselisih
            $routeGroups = [];
            foreach ($request->schedule_rutes as $rute) {
                $routeKey = $rute['route_id'];
                $departureTime = $rute['departure_time'];
                
                if (!isset($routeGroups[$routeKey])) {
                    $routeGroups[$routeKey] = [];
                }
                
                $routeGroups[$routeKey][] = $departureTime;
            }

            // Cek untuk setiap rute
            foreach ($routeGroups as $routeId => $times) {
                if (count($times) !== count(array_unique($times))) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Tidak boleh ada rute yang sama dengan waktu keberangkatan yang sama'
                    ], 422);
                }
            }

            $schedule = Schedules::create([
                'location_id' => $request->location_id,
                'bus_id' => $request->bus_id,
                'supir_id' => $request->supir_id,
                'departure_time' => $request->departure_time,
                'arrival_time' => $request->arrival_time,
                'description' => $request->description ?? null,
                'created_by_id' => $request->user()->id,
                'updated_by_id' => $request->user()->id,
            ]);

            foreach ($request->schedule_rutes as $rute) {
                ScheduleRute::create([
                    'schedule_id' => $schedule->id,
                    'route_id' => $rute['route_id'],
                    'sequence_route' => $rute['sequence_route'],
                    'departure_time' => $rute['departure_time'],
                    'arrival_time' => $rute['arrival_time'],
                    'price_rute' => $rute['price_rute'],
                    'description' => $rute['description'] ?? null,
                    'is_active' => $rute['is_active'],
                    'created_by_id' => $request->user()->id,
                    'updated_by_id' => $request->user()->id,
                ]);
            }

            return response()->json(['message' => 'Schedule created successfully', 'schedule' => $schedule], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create schedule', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateSchedulesRequest $request, $id)
    {
        $schedule = Schedules::find($id);

        if (!$schedule) {
            return response()->json(['message' => 'Schedule not found'], 404);
        }

        try {
            // Validasi input wajib
            if (!$request->bus_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Bus harus dipilih'
                ], 422);
            }

            if (!$request->supir_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Supir harus dipilih'
                ], 422);
            }

            if (!$request->location_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Lokasi harus dipilih'
                ], 422);
            }

            // Validasi minimal satu rute
            if (!$request->schedule_rutes || count($request->schedule_rutes) < 1) {
                return response()->json([
                    'status' => false,
                    'message' => 'Minimal harus mengisi satu rute'
                ], 422);
            }

            // Validasi waktu keberangkatan yang berselisih
            $routeGroups = [];
            foreach ($request->schedule_rutes as $id => $ruteData) {
                $routeKey = $ruteData['route_id'];
                $departureTime = $ruteData['departure_time'];
                
                if (!isset($routeGroups[$routeKey])) {
                    $routeGroups[$routeKey] = [];
                }
                
                $routeGroups[$routeKey][] = $departureTime;
            }

            // Cek untuk setiap rute
            foreach ($routeGroups as $routeId => $times) {
                if (count($times) !== count(array_unique($times))) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Tidak boleh ada rute yang sama dengan waktu keberangkatan yang sama'
                    ], 422);
                }
            }

            // Memperbarui informasi jadwal
            $schedule->update([
                'location_id' => $request->location_id,
                'bus_id' => $request->bus_id,
                'supir_id' => $request->supir_id,
                'departure_time' => $request->departure_time,
                'arrival_time' => $request->arrival_time,
                'description' => $request->description ?? null,
                'updated_by_id' => $request->user()->id
            ]);

            // Menyimpan ID rute yang ada
            $existingRuteIds = $schedule->scheduleRutes()->pluck('id')->toArray();
            $newRuteIds = [];

            // Memproses rute yang baru
            foreach ($request->schedule_rutes as $id => $ruteData) {
                // Periksa apakah 'id' ada dalam data yang dikirim
                if (!isset($ruteData)) {
                    return response()->json(['message' => 'Route data is required'], 400);
                }

                // Cari scheduleRute berdasarkan id
                $scheduleRute = ScheduleRute::find($id);
                if ($scheduleRute) {
                    // Update jika ditemukan
                    $scheduleRute->update([
                        'route_id' => $ruteData['route_id'],
                        'sequence_route' => $ruteData['sequence_route'],
                        'departure_time' => $ruteData['departure_time'],
                        'arrival_time' => $ruteData['arrival_time'],
                        'price_rute' => $ruteData['price_rute'],
                        'description' => $ruteData['description'] ?? null,
                        'is_active' => $ruteData['is_active'],
                        'updated_by_id' => $request->user()->id,
                    ]);
                    $newRuteIds[] = $scheduleRute->id; // Simpan ID rute yang diperbarui
                } else {
                    // Jika tidak ditemukan, buat baru
                    $newRute = ScheduleRute::create([
                        'schedule_id' => $schedule->id,
                        'route_id' => $ruteData['route_id'],
                        'sequence_route' => $ruteData['sequence_route'],
                        'departure_time' => $ruteData['departure_time'],
                        'arrival_time' => $ruteData['arrival_time'],
                        'price_rute' => $ruteData['price_rute'],
                        'description' => $ruteData['description'] ?? null,
                        'is_active' => $ruteData['is_active'],
                        'created_by_id' => $request->user()->id,
                        'updated_by_id' => $request->user()->id,
                    ]);
                    $newRuteIds[] = $newRute->id; // Simpan ID rute baru
                }
            }

            // Hapus rute yang tidak ada dalam data baru
            $rutesToDelete = array_diff($existingRuteIds, $newRuteIds);
            ScheduleRute::destroy($rutesToDelete);

            return response()->json(['message' => 'Schedule updated successfully', 'schedule' => $schedule], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update schedule', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $schedule = Schedules::find($id);

            if (!$schedule) {
                return response()->json(['message' => 'Schedule not found'], 404);
            }


            $schedule->delete();

            return response()->json(['message' => 'Schedule deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete schedule', 'error' => $e->getMessage()], 500);
        }
    }
}

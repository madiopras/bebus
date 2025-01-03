<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedules\StoreSchedulesRequest;
use App\Http\Requests\Schedules\UpdateSchedulesRequest;
use App\Models\Schedules;
use App\Models\ScheduleRute;
use Illuminate\Http\Request;

class SchedulesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['name', 'departure_time', 'bus_number', 'class_name', 'type_bus', 'bus_name', 'is_active']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $schedules = Schedules::filterWithJoin($filters)->paginate($limit, ['*'], 'page', $page);

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
            $schedule = Schedules::create([
                'location_id' => $request->location_id,
                'bus_id' => $request->bus_id,
                'supir_id' => $request->supir_id,
                'departure_time' => $request->departure_time,
                'arrival_time' => $request->arrival_time,
                'description' => $request->description,
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
                    'description' => $rute['description'],
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
        // Memperbarui informasi jadwal
        $schedule->update($request->only(['location_id', 'bus_id', 'supir_id', 'departure_time', 'arrival_time', 'description']));
        $schedule->updated_by_id = $request->user()->id;
        $schedule->save();

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
                    'description' => $ruteData['description'],
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
                    'description' => $ruteData['description'],
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

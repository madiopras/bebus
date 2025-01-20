<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\UtilityBBM;
use App\Models\Schedules;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UtilityBBMController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $utilityBBMs = UtilityBBM::with(['schedule.bus'])
                ->select([
                    'utility_bbm.id',
                    'utility_bbm.tanggal',
                    'utility_bbm.schedule_id',
                    'utility_bbm.nomor_jadwal_bus',
                    'utility_bbm.odometer_awal',
                    'utility_bbm.jarak',
                    'utility_bbm.harga_liter_bbm',
                    'utility_bbm.total_perkiraan_harga_bbm',
                    'utility_bbm.total_aktual_harga_bbm',
                    'utility_bbm.description',
                    'utility_bbm.created_at',
                    'utility_bbm.updated_at'
                ])
                ->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'data' => $utilityBBMs->items(),
                'current_page' => $utilityBBMs->currentPage(),
                'total_pages' => $utilityBBMs->lastPage(),
                'total_items' => $utilityBBMs->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data BBM',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDataCreate(): JsonResponse
    {
        try {
            $schedules = Schedules::select(
                    'schedules.id',
                    'buses.bus_name',
                    'schedules.departure_time'
                )
                ->join('buses', 'schedules.bus_id', '=', 'buses.id')
                ->with(['scheduleRutes.rute' => function($query) {
                    $query->select('id', 'distance');
                }])
                ->get()
                ->map(function($schedule) {
                    $totalDistance = $schedule->scheduleRutes->sum(function($scheduleRute) {
                        return $scheduleRute->rute->distance;
                    });
                    
                    return [
                        'id' => $schedule->id,
                        'nomor_jadwal_bus' => $schedule->bus_name . ' - ' . $schedule->departure_time->format('d M Y H:i'),
                        'jarak' => $totalDistance
                    ];
                });

            return response()->json([
                'status' => true,
                'data' => $schedules
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data jadwal',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'tanggal' => 'required|date',
                'schedule_id' => 'required|exists:schedules,id',
                'odometer_awal' => 'required|numeric|min:0',
                'jarak' => 'required|numeric|min:0',
                'harga_liter_bbm' => 'required|numeric|min:0',
                'total_perkiraan_harga_bbm' => 'required|numeric|min:0',
                'total_aktual_harga_bbm' => 'required|numeric|min:0',
                'description' => 'nullable|string|max:1000'
            ]);

            // Get schedule data for nomor_jadwal_bus
            $schedule = Schedules::with('bus')->findOrFail($validated['schedule_id']);
            $validated['nomor_jadwal_bus'] = $schedule->bus->bus_name . ' - ' . 
                $schedule->departure_time->format('d M Y H:i');

            $utilityBBM = UtilityBBM::create($validated);

            return response()->json([
                'status' => true,
                'message' => 'Data BBM berhasil ditambahkan',
                'data' => $utilityBBM
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menambahkan data BBM',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id): JsonResponse
    {
        try {
            $utilityBBM = UtilityBBM::with('schedule.bus')->findOrFail($id);

            return response()->json([
                'status' => true,
                'data' => $utilityBBM
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Data BBM tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function update(Request $request, $id): JsonResponse
    {
        try {
            $utilityBBM = UtilityBBM::findOrFail($id);

            $validated = $request->validate([
                'tanggal' => 'required|date',
                'schedule_id' => 'required|exists:schedules,id',
                'odometer_awal' => 'required|numeric|min:0',
                'jarak' => 'required|numeric|min:0',
                'harga_liter_bbm' => 'required|numeric|min:0',
                'total_perkiraan_harga_bbm' => 'required|numeric|min:0',
                'total_aktual_harga_bbm' => 'required|numeric|min:0',
                'description' => 'nullable|string|max:1000'
            ]);

            // Update nomor_jadwal_bus if schedule_id changed
            if ($utilityBBM->schedule_id != $validated['schedule_id']) {
                $schedule = Schedules::with('bus')->findOrFail($validated['schedule_id']);
                $validated['nomor_jadwal_bus'] = $schedule->bus->bus_name . ' - ' . 
                    $schedule->departure_time->format('d M Y H:i');
            }

            $utilityBBM->update($validated);

            return response()->json([
                'status' => true,
                'message' => 'Data BBM berhasil diperbarui',
                'data' => $utilityBBM
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui data BBM',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $utilityBBM = UtilityBBM::findOrFail($id);
            $utilityBBM->delete();

            return response()->json([
                'status' => true,
                'message' => 'Data BBM berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus data BBM',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }
}

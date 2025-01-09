<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ScheduleRute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScheduleRuteController extends Controller
{
    public function index(Request $request)
    {
        try {
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);
            $filters = $request->only(['dari', 'ke', 'tanggal', 'bus_number', 'type_bus']);

            $query = DB::table('schedules as s')
                ->select(
                    'sr.schedule_id',
                    'sr.id as schedule_rute_id',
                    DB::raw("DATE_FORMAT(sr.departure_time, '%Y-%m-%d %H:%i:%s') as departure_time"),
                    DB::raw("DATE_FORMAT(sr.arrival_time, '%Y-%m-%d %H:%i:%s') as arrival_time"),
                    'sr.is_active',
                    'l.name as dari',
                    'l2.name as ke',
                    'r.price',
                    'b.bus_number as kode_bus',
                    'b.bus_name as nama_bus',
                    'b.type_bus as tipe_bus',
                    'c.class_name as kelas_bus',
                    'u.name as supir'
                )
                ->leftJoin('schedule_rute as sr', 's.id', '=', 'sr.schedule_id')
                ->join('routes as r', 'r.id', '=', 'sr.route_id')
                ->leftJoin('locations as l', 'r.start_location_id', '=', 'l.id')
                ->leftJoin('locations as l2', 'r.end_location_id', '=', 'l2.id')
                ->leftJoin('buses as b', 's.bus_id', '=', 'b.id')
                ->leftJoin('users as u', 's.supir_id', '=', 'u.id')
                ->leftJoin('classes as c', 'b.class_id', '=', 'c.id')
                ->where('s.departure_time', '>', now());

            // Apply filters
            if (isset($filters['dari'])) {
                $query->where('l.name', 'like', '%' . $filters['dari'] . '%');
            }
            if (isset($filters['ke'])) {
                $query->where('l2.name', 'like', '%' . $filters['ke'] . '%');
            }
            if (isset($filters['tanggal'])) {
                $query->whereDate('s.departure_time', $filters['tanggal']);
            }
            if (isset($filters['bus_number'])) {
                $query->where('b.bus_number', 'like', '%' . $filters['bus_number'] . '%');
            }
            if (isset($filters['type_bus'])) {
                $query->where('b.type_bus', 'like', '%' . $filters['type_bus'] . '%');
            }

            $scheduleRutes = $query->paginate($limit);

            return response()->json([
                'status' => true,
                'data' => $scheduleRutes->items(),
                'current_page' => $scheduleRutes->currentPage(),
                'total_pages' => $scheduleRutes->lastPage(),
                'total_items' => $scheduleRutes->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data jadwal rute',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'departure_time' => 'required|date',
                'arrival_time' => 'required|date|after:departure_time',
                'is_active' => 'required|boolean'
            ]);

            $scheduleRute = ScheduleRute::findOrFail($id);
            
            $scheduleRute->update([
                'departure_time' => $request->departure_time,
                'arrival_time' => $request->arrival_time,
                'is_active' => $request->is_active,
                'updated_by_id' => $request->user()->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Jadwal rute berhasil diperbarui',
                'data' => $scheduleRute
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui jadwal rute',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 
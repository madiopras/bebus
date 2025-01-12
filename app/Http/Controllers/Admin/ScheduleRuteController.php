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
            $filters = $request->only(['dari', 'ke', 'tanggal', 'bus_number', 'type_bus', 'bus_id', 'driver_id', 'is_active']);

            $query = DB::table('schedules as s')
                ->select(
                    'sr.schedule_id',
                    'sr.id as schedule_rute_id',
                    DB::raw("DATE_FORMAT(sr.departure_time, '%Y-%m-%d %H:%i:%s') as departure_time"),
                    DB::raw("DATE_FORMAT(sr.arrival_time, '%Y-%m-%d %H:%i:%s') as arrival_time"),
                    'sr.is_active',
                    'l.name as dari',
                    'l2.name as ke',
                    'sr.price_rute',
                    'b.bus_number as kode_bus',
                    'b.bus_name as nama_bus',
                    'b.type_bus as tipe_bus',
                    'c.class_name as kelas_bus',
                    'u.name as supir',
                    DB::raw('(SELECT COUNT(*) FROM seats WHERE bus_id = b.id AND is_active = 1) as total_seats')
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
            if ($request->has('departure_start')) {
                $query->whereRaw('DATE(sr.departure_time) >= ?', [$request->departure_start]);
            }
            if ($request->has('departure_end')) {
                $query->whereRaw('DATE(sr.departure_time) <= ?', [$request->departure_end]);
            }
            if (isset($filters['dari'])) {
                $query->where('l.id', $filters['dari']);
            }
            if (isset($filters['ke'])) {
                $query->where('l2.id', $filters['ke']);
            }
            if (isset($filters['bus_id'])) {
                $query->where('b.id', $filters['bus_id']);
            }
            if (isset($filters['driver_id'])) {
                $query->where('u.id', $filters['driver_id']);
            }
            if (isset($filters['is_active'])) {
                $query->where('sr.is_active', $filters['is_active']);
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
                'is_active' => 'required|boolean',
                'price_rute' => 'required|numeric|min:0'
            ]);

            $scheduleRute = ScheduleRute::findOrFail($id);
            
            $scheduleRute->update([
                'departure_time' => $request->departure_time,
                'arrival_time' => $request->arrival_time,
                'is_active' => $request->is_active,
                'price_rute' => $request->price_rute,
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

    public function getSeats($id)
    {
        try {
            // Ambil detail rute
            $rute = DB::table('schedule_rute as sr')
                ->select(
                    'sr.id as schedule_rute_id',
                    'sr.route_id',
                    'sr.departure_time',
                    'sr.arrival_time',
                    'sr.price_rute',
                    'r.start_location_id',
                    'r.end_location_id',
                    'l1.name as start_location',
                    'l2.name as end_location',
                    'r.distance'
                )
                ->join('routes as r', 'sr.route_id', '=', 'r.id')
                ->join('locations as l1', 'r.start_location_id', '=', 'l1.id')
                ->join('locations as l2', 'r.end_location_id', '=', 'l2.id')
                ->where('sr.id', $id)
                ->first();

            if (!$rute) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data rute tidak ditemukan'
                ], 404);
            }

            // Ambil detail bus
            $bus = DB::table('schedule_rute as sr')
                ->select(
                    'b2.id as bus_id',
                    'b2.bus_number',
                    'b2.type_bus',
                    'b2.bus_name',
                    'b2.capacity',
                    'c.class_name',
                    'b2.cargo'
                )
                ->join('schedules as s', 'sr.schedule_id', '=', 's.id')
                ->join('buses as b2', 's.bus_id', '=', 'b2.id')
                ->leftJoin('classes as c', 'b2.class_id', '=', 'c.id')
                ->where('sr.id', $id)
                ->first();

            // Ambil data kursi
            $seats = DB::table('seats as s2')
                ->select(
                    's2.seat_number',
                    DB::raw('CASE 
                        WHEN s2.is_active = 0 THEN "R"
                        ELSE COALESCE(p.gender, NULL)
                    END as gender')
                )
                ->join('buses as b2', 's2.bus_id', '=', 'b2.id')
                ->join('schedules as s', 'b2.id', '=', 's.bus_id')
                ->join('schedule_rute as sr', 's.id', '=', 'sr.schedule_id')
                ->leftJoin('bookings as b', 'b.schedule_id', '=', 'sr.id')
                ->leftJoin('passengers as p', function($join) {
                    $join->on('b.id', '=', 'p.booking_id')
                         ->on('s2.id', '=', 'p.schedule_seat_id');
                })
                ->where('sr.id', $id)
                ->orderBy('s2.seat_number')
                ->distinct()
                ->get();

            if ($seats->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data kursi tidak ditemukan'
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data' => [
                    'rute' => $rute,
                    'bus' => $bus,
                    'seats' => $seats
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data kursi',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 
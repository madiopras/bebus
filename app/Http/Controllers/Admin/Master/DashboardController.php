<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\Bookings;
use App\Models\Schedules;
use App\Models\UtilityBBM;
use App\Models\ScheduleRute;
use App\Models\Routes;
use App\Models\Locations;
use App\Models\Buses;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            $startDate = Carbon::parse($request->input('start_date', Carbon::now()->startOfYear()))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date', Carbon::now()))->endOfDay();

            // Summary Data
            $totalTrips = Schedules::whereBetween('departure_time', [$startDate, $endDate])
                ->count();

            // Total semua booking
            $totalBookings = Bookings::select('bookings.*')
                ->leftJoin('schedule_rute as sr', 'bookings.schedule_id', '=', 'sr.id')
                ->leftJoin('routes as r', 'sr.route_id', '=', 'r.id')
                ->leftJoin('locations as l', 'r.start_location_id', '=', 'l.id')
                ->leftJoin('locations as l2', 'r.end_location_id', '=', 'l2.id')
                ->leftJoin('schedules as s', 'sr.schedule_id', '=', 's.id')
                ->leftJoin('buses as b', 's.bus_id', '=', 'b.id')
                ->whereDate('bookings.booking_date', '>=', $startDate)
                ->whereDate('bookings.booking_date', '<=', $endDate)
                ->count();

            // Total passengers (hanya yang PAID) dan total income
            $bookingStats = Bookings::whereBetween('booking_date', [$startDate, $endDate])
                ->where('payment_status', 'PAID')
                ->select(
                    DB::raw('COUNT(DISTINCT id) as total_passengers'),
                    DB::raw('SUM(final_price) as total_income')
                )
                ->first();

            $totalFuel = UtilityBBM::join('schedules', 'utility_bbm.schedule_id', '=', 'schedules.id')
                ->whereBetween('schedules.departure_time', [$startDate, $endDate])
                ->sum('total_aktual_harga_bbm');

            // Chart Data
            $tripsByRoute = ScheduleRute::join('schedules', 'schedule_rute.schedule_id', '=', 'schedules.id')
                ->join('routes', 'schedule_rute.route_id', '=', 'routes.id')
                ->join('locations as start_loc', 'routes.start_location_id', '=', 'start_loc.id')
                ->join('locations as end_loc', 'routes.end_location_id', '=', 'end_loc.id')
                ->whereBetween('schedules.departure_time', [$startDate, $endDate])
                ->select(
                    DB::raw("CONCAT(start_loc.name, ' - ', end_loc.name) as route"),
                    DB::raw('COUNT(DISTINCT schedules.id) as trips'),
                    DB::raw('SUM(routes.distance) as distance'),
                    DB::raw('SUM(schedule_rute.price_rute) as revenue')
                )
                ->groupBy('routes.id', 'start_loc.name', 'end_loc.name')
                ->get();

            $passengersByClass = Bookings::join('schedules', 'bookings.schedule_id', '=', 'schedules.id')
                ->join('buses', 'schedules.bus_id', '=', 'buses.id')
                ->join('classes', 'buses.class_id', '=', 'classes.id')
                ->whereBetween('bookings.booking_date', [$startDate, $endDate])
                ->where('bookings.payment_status', 'PAID')
                ->select(
                    'classes.class_name as class',
                    DB::raw('COUNT(DISTINCT bookings.id) as passengers'),
                    DB::raw('SUM(bookings.final_price) as revenue')
                )
                ->groupBy('classes.id', 'classes.class_name')
                ->get();

            $dailyStats = DB::table(function($query) use ($startDate, $endDate) {
                $query->from('schedules')
                    ->select(
                        DB::raw('DATE(departure_time) as date'),
                        DB::raw('COUNT(DISTINCT schedules.id) as trips'),
                        'schedules.id as schedule_id'
                    )
                    ->whereBetween('departure_time', [$startDate, $endDate])
                    ->groupBy('date', 'schedules.id');
            }, 'daily_schedules')
            ->select(
                'date',
                DB::raw('SUM(trips) as trips'),
                DB::raw('(
                    SELECT COUNT(DISTINCT b.id)
                    FROM bookings b
                    JOIN schedules s ON s.id = b.schedule_id
                    WHERE DATE(s.departure_time) = daily_schedules.date
                    AND b.payment_status = "PAID"
                ) as passengers'),
                DB::raw('(
                    SELECT SUM(b.final_price)
                    FROM bookings b
                    JOIN schedules s ON s.id = b.schedule_id
                    WHERE DATE(s.departure_time) = daily_schedules.date
                    AND b.payment_status = "PAID"
                ) as revenue'),
                DB::raw('(
                    SELECT SUM(u.total_aktual_harga_bbm)
                    FROM utility_bbm u
                    JOIN schedules s ON s.id = u.schedule_id
                    WHERE DATE(s.departure_time) = daily_schedules.date
                ) as fuel')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

            return response()->json([
                'status' => true,
                'data' => [
                    'summary' => [
                        'totalTrips' => $totalTrips,
                        'totalPassengers' => $bookingStats->total_passengers ?? 0,
                        'totalFuel' => $totalFuel ?? 0,
                        'totalIncome' => $bookingStats->total_income ?? 0,
                        'totalBookings' => $totalBookings ?? 0
                    ],
                    'charts' => [
                        'tripsByRoute' => $tripsByRoute,
                        'passengersByClass' => $passengersByClass,
                        'dailyStats' => $dailyStats
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch dashboard data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function summary(Request $request)
    {
        $startDate = Carbon::parse($request->input('start_date', Carbon::now()->startOfYear()))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', Carbon::now()))->endOfDay();

        // Total Trips
        $totalTrips = Schedules::whereBetween('departure_time', [$startDate, $endDate])
            ->count();

        // Total semua booking
        $totalBookings = Bookings::select('bookings.*')
            ->leftJoin('schedule_rute as sr', 'bookings.schedule_id', '=', 'sr.id')
            ->leftJoin('routes as r', 'sr.route_id', '=', 'r.id')
            ->leftJoin('locations as l', 'r.start_location_id', '=', 'l.id')
            ->leftJoin('locations as l2', 'r.end_location_id', '=', 'l2.id')
            ->leftJoin('schedules as s', 'sr.schedule_id', '=', 's.id')
            ->leftJoin('buses as b', 's.bus_id', '=', 'b.id')
            ->whereDate('bookings.booking_date', '>=', $startDate)
            ->whereDate('bookings.booking_date', '<=', $endDate)
            ->count();

        // Total Passengers (hanya yang PAID) dan Total Income
        $bookingStats = Bookings::whereBetween('booking_date', [$startDate, $endDate])
            ->where('payment_status', 'PAID')
            ->select(
                DB::raw('COUNT(DISTINCT id) as total_passengers'),
                DB::raw('SUM(final_price) as total_income')
            )
            ->first();

        // Total Fuel Cost
        $totalFuel = UtilityBBM::join('schedules', 'utility_bbm.schedule_id', '=', 'schedules.id')
            ->whereBetween('schedules.departure_time', [$startDate, $endDate])
            ->sum('total_aktual_harga_bbm');

        return response()->json([
            'totalTrips' => $totalTrips,
            'totalPassengers' => $bookingStats->total_passengers ?? 0,
            'totalFuel' => $totalFuel ?? 0,
            'totalIncome' => $bookingStats->total_income ?? 0,
            'totalBookings' => $totalBookings ?? 0
        ]);
    }

    public function charts(Request $request)
    {
        $startDate = Carbon::parse($request->input('start_date', Carbon::now()->startOfYear()))->startOfDay();
        $endDate = Carbon::parse($request->input('end_date', Carbon::now()))->endOfDay();

        // Trips by Route
        $tripsByRoute = ScheduleRute::join('schedules', 'schedule_rute.schedule_id', '=', 'schedules.id')
            ->join('routes', 'schedule_rute.route_id', '=', 'routes.id')
            ->join('locations as start_loc', 'routes.start_location_id', '=', 'start_loc.id')
            ->join('locations as end_loc', 'routes.end_location_id', '=', 'end_loc.id')
            ->whereBetween('schedules.departure_time', [$startDate, $endDate])
            ->select(
                DB::raw("CONCAT(start_loc.name, ' - ', end_loc.name) as route"),
                DB::raw('COUNT(DISTINCT schedules.id) as trips'),
                DB::raw('SUM(routes.distance) as distance'),
                DB::raw('SUM(schedule_rute.price_rute) as revenue')
            )
            ->groupBy('routes.id', 'start_loc.name', 'end_loc.name')
            ->get();

        // Passengers by Class
        $passengersByClass = Bookings::join('schedules', 'bookings.schedule_id', '=', 'schedules.id')
            ->join('buses', 'schedules.bus_id', '=', 'buses.id')
            ->join('classes', 'buses.class_id', '=', 'classes.id')
            ->whereBetween('bookings.booking_date', [$startDate, $endDate])
            ->where('bookings.payment_status', 'PAID')
            ->select(
                'classes.class_name as class',
                DB::raw('COUNT(DISTINCT bookings.id) as passengers'),
                DB::raw('SUM(bookings.final_price) as revenue')
            )
            ->groupBy('classes.id', 'classes.class_name')
            ->get();

        // Daily Stats
        $dailyStats = DB::table(function($query) use ($startDate, $endDate) {
            $query->from('schedules')
                ->select(
                    DB::raw('DATE(departure_time) as date'),
                    DB::raw('COUNT(DISTINCT schedules.id) as trips'),
                    'schedules.id as schedule_id'
                )
                ->whereBetween('departure_time', [$startDate, $endDate])
                ->groupBy('date', 'schedules.id');
        }, 'daily_schedules')
        ->select(
            'date',
            DB::raw('SUM(trips) as trips'),
            DB::raw('(
                SELECT COUNT(DISTINCT b.id)
                FROM bookings b
                JOIN schedules s ON s.id = b.schedule_id
                WHERE DATE(s.departure_time) = daily_schedules.date
                AND b.payment_status = "PAID"
            ) as passengers'),
            DB::raw('(
                SELECT SUM(b.final_price)
                FROM bookings b
                JOIN schedules s ON s.id = b.schedule_id
                WHERE DATE(s.departure_time) = daily_schedules.date
                AND b.payment_status = "PAID"
            ) as revenue'),
            DB::raw('(
                SELECT SUM(u.total_aktual_harga_bbm)
                FROM utility_bbm u
                JOIN schedules s ON s.id = u.schedule_id
                WHERE DATE(s.departure_time) = daily_schedules.date
            ) as fuel')
        )
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        return response()->json([
            'tripsByRoute' => $tripsByRoute,
            'passengersByClass' => $passengersByClass,
            'dailyStats' => $dailyStats
        ]);
    }

    public function operationalTable(Request $request)
    {
        try {
            $startDate = Carbon::parse($request->input('start_date', Carbon::now()->startOfYear()))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date', Carbon::now()))->endOfDay();
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search', '');
            $sortBy = $request->input('sort_by', 'bus_number');
            $sortDir = $request->input('sort_dir', 'asc');

            $query = Buses::select([
                'buses.id',
                'buses.bus_number',
                'classes.class_name as bus_class',
                DB::raw('COUNT(DISTINCT schedules.id) as total_trips'),
                DB::raw('COUNT(DISTINCT CASE WHEN bookings.payment_status = "PAID" THEN bookings.id END) as total_passengers'),
                DB::raw('SUM(utility_bbm.total_aktual_harga_bbm) as fuel_cost'),
                DB::raw('SUM(CASE WHEN bookings.payment_status = "PAID" THEN bookings.final_price ELSE 0 END) as revenue'),
                DB::raw('CASE 
                    WHEN EXISTS (
                        SELECT 1 
                        FROM schedules s2 
                        WHERE s2.bus_id = buses.id 
                        AND s2.departure_time <= NOW() 
                        AND s2.arrival_time >= NOW()
                    ) THEN "Beroperasi"
                    ELSE "Tidak Beroperasi"
                END as status')
            ])
            ->leftJoin('classes', 'buses.class_id', '=', 'classes.id')
            ->leftJoin('schedules', function($join) use ($startDate, $endDate) {
                $join->on('schedules.bus_id', '=', 'buses.id')
                     ->whereDate('schedules.departure_time', '>=', $startDate)
                     ->whereDate('schedules.departure_time', '<=', $endDate);
            })
            ->leftJoin('schedule_rute', 'schedule_rute.schedule_id', '=', 'schedules.id')
            ->leftJoin('routes', 'schedule_rute.route_id', '=', 'routes.id')
            ->leftJoin('locations as start_loc', 'routes.start_location_id', '=', 'start_loc.id')
            ->leftJoin('locations as end_loc', 'routes.end_location_id', '=', 'end_loc.id')
            ->leftJoin('bookings', 'bookings.schedule_id', '=', 'schedules.id')
            ->leftJoin('utility_bbm', 'utility_bbm.schedule_id', '=', 'schedules.id')
            ->groupBy('buses.id', 'buses.bus_number', 'classes.class_name');

            // Menambahkan kondisi pencarian
            if (!empty($search)) {
                $query->where(function($q) use ($search) {
                    $q->where('buses.bus_number', 'like', "%{$search}%")
                      ->orWhere('classes.class_name', 'like', "%{$search}%");
                });
            }

            // Menambahkan pengurutan
            switch ($sortBy) {
                case 'bus_number':
                    $query->orderBy('buses.bus_number', $sortDir);
                    break;
                case 'bus_class':
                    $query->orderBy('classes.class_name', $sortDir);
                    break;
                case 'total_trips':
                    $query->orderBy('total_trips', $sortDir);
                    break;
                case 'total_passengers':
                    $query->orderBy('total_passengers', $sortDir);
                    break;
                case 'fuel_cost':
                    $query->orderBy('fuel_cost', $sortDir);
                    break;
                case 'revenue':
                    $query->orderBy('revenue', $sortDir);
                    break;
                default:
                    $query->orderBy('buses.bus_number', 'asc');
            }

            $result = $query->paginate($perPage);

            // Mengambil rute untuk setiap bus
            foreach ($result->items() as $bus) {
                $routes = DB::table('schedules')
                    ->select(DB::raw("CONCAT(start_loc.name, ' - ', end_loc.name) as route"))
                    ->join('schedule_rute', 'schedule_rute.schedule_id', '=', 'schedules.id')
                    ->join('routes', 'schedule_rute.route_id', '=', 'routes.id')
                    ->join('locations as start_loc', 'routes.start_location_id', '=', 'start_loc.id')
                    ->join('locations as end_loc', 'routes.end_location_id', '=', 'end_loc.id')
                    ->where('schedules.bus_id', $bus->id)
                    ->whereDate('schedules.departure_time', '>=', $startDate)
                    ->whereDate('schedules.departure_time', '<=', $endDate)
                    ->groupBy('route')
                    ->pluck('route')
                    ->first();

                $bus->route = $routes ?? '-';
                $bus->fuel_consumption = round($bus->fuel_cost / 15000); // Asumsi harga BBM per liter
                $bus->fuel_cost = (int)$bus->fuel_cost;
                $bus->revenue = (int)$bus->revenue;
            }

            return response()->json([
                'data' => $result->items(),
                'meta' => [
                    'current_page' => $result->currentPage(),
                    'per_page' => $result->perPage(),
                    'total_items' => $result->total(),
                    'total_pages' => $result->lastPage()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal mengambil data operasional',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 
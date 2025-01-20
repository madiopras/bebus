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
    public function getAllDashboard(Request $request)
    {
        try {
            $startDate = Carbon::parse($request->input('start_date', Carbon::now()->startOfYear()))->startOfDay();
            $endDate = Carbon::parse($request->input('end_date', Carbon::now()))->endOfDay();
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search', '');
            $sortBy = $request->input('sort_by', 'bus_number');
            $sortDir = $request->input('sort_dir', 'asc');
            
            // Filter parameters
            $busId = $request->input('bus_id');
            $routeId = $request->input('route_id');
            $classId = $request->input('class_id');

            // Summary Data
            $totalTrips = Schedules::when($busId, function($query) use ($busId) {
                    return $query->where('bus_id', $busId);
                })
                ->whereBetween('departure_time', [$startDate, $endDate])
                ->count();

            $totalBookings = Bookings::select('bookings.*')
                ->leftJoin('schedule_rute as sr', 'bookings.schedule_id', '=', 'sr.id')
                ->leftJoin('schedules as s', 'sr.schedule_id', '=', 's.id')
                ->leftJoin('buses as b', 's.bus_id', '=', 'b.id')
                ->when($busId, function($query) use ($busId) {
                    return $query->where('s.bus_id', $busId);
                })
                ->when($routeId, function($query) use ($routeId) {
                    return $query->where('sr.route_id', $routeId);
                })
                ->when($classId, function($query) use ($classId) {
                    return $query->where('b.class_id', $classId);
                })
                ->whereDate('bookings.booking_date', '>=', $startDate)
                ->whereDate('bookings.booking_date', '<=', $endDate)
                ->count();

            $bookingStats = Bookings::join('schedule_rute as sr', 'bookings.schedule_id', '=', 'sr.id')
                ->join('schedules as s', 'sr.schedule_id', '=', 's.id')
                ->join('buses as b', 's.bus_id', '=', 'b.id')
                ->when($busId, function($query) use ($busId) {
                    return $query->where('s.bus_id', $busId);
                })
                ->when($routeId, function($query) use ($routeId) {
                    return $query->where('sr.route_id', $routeId);
                })
                ->when($classId, function($query) use ($classId) {
                    return $query->where('b.class_id', $classId);
                })
                ->whereBetween('booking_date', [$startDate, $endDate])
                ->where('payment_status', 'PAID')
                ->select(
                    DB::raw('COUNT(DISTINCT bookings.id) as total_passengers'),
                    DB::raw('SUM(bookings.final_price) as total_income')
                )
                ->first();

            $totalFuel = UtilityBBM::join('schedules', 'utility_bbm.schedule_id', '=', 'schedules.id')
                ->when($busId, function($query) use ($busId) {
                    return $query->where('schedules.bus_id', $busId);
                })
                ->whereBetween('schedules.departure_time', [$startDate, $endDate])
                ->sum('total_aktual_harga_bbm');

            // Chart Data
            $tripsByRoute = ScheduleRute::join('schedules', 'schedule_rute.schedule_id', '=', 'schedules.id')
                ->join('routes', 'schedule_rute.route_id', '=', 'routes.id')
                ->join('locations as start_loc', 'routes.start_location_id', '=', 'start_loc.id')
                ->join('locations as end_loc', 'routes.end_location_id', '=', 'end_loc.id')
                ->when($busId, function($query) use ($busId) {
                    return $query->where('schedules.bus_id', $busId);
                })
                ->when($routeId, function($query) use ($routeId) {
                    return $query->where('schedule_rute.route_id', $routeId);
                })
                ->whereBetween('schedules.departure_time', [$startDate, $endDate])
                ->select(
                    DB::raw("CONCAT(start_loc.name, ' - ', end_loc.name) as route"),
                    DB::raw('COUNT(DISTINCT schedules.id) as trips'),
                    DB::raw('SUM(routes.distance) as distance'),
                    DB::raw('SUM(schedule_rute.price_rute) as revenue')
                )
                ->groupBy('routes.id', 'start_loc.name', 'end_loc.name')
                ->get();

            $passengersByClass = Bookings::join('schedule_rute', 'bookings.schedule_id', '=', 'schedule_rute.id')
                ->join('schedules', 'schedule_rute.schedule_id', '=', 'schedules.id')
                ->join('buses', 'schedules.bus_id', '=', 'buses.id')
                ->join('classes', 'buses.class_id', '=', 'classes.id')
                ->when($busId, function($query) use ($busId) {
                    return $query->where('schedules.bus_id', $busId);
                })
                ->when($routeId, function($query) use ($routeId) {
                    return $query->where('schedule_rute.route_id', $routeId);
                })
                ->when($classId, function($query) use ($classId) {
                    return $query->where('buses.class_id', $classId);
                })
                ->whereBetween('bookings.booking_date', [$startDate, $endDate])
                ->where('bookings.payment_status', 'PAID')
                ->select(
                    'classes.class_name as class',
                    DB::raw('COUNT(DISTINCT bookings.id) as passengers'),
                    DB::raw('SUM(bookings.final_price) as revenue')
                )
                ->groupBy('classes.id', 'classes.class_name')
                ->get();

            $dailyStats = DB::table(function($query) use ($startDate, $endDate, $busId, $routeId) {
                $query->from('schedules')
                    ->select(
                        DB::raw('DATE(departure_time) as date'),
                        DB::raw('COUNT(DISTINCT schedules.id) as trips'),
                        'schedules.id as schedule_id'
                    )
                    ->when($busId, function($q) use ($busId) {
                        return $q->where('schedules.bus_id', $busId);
                    })
                    ->whereBetween('departure_time', [$startDate, $endDate])
                    ->groupBy('date', 'schedules.id');
            }, 'daily_schedules')
            ->select(
                'date',
                DB::raw('SUM(trips) as trips'),
                DB::raw('(
                    SELECT COUNT(DISTINCT b.id)
                    FROM bookings b
                    JOIN schedule_rute sr ON sr.id = b.schedule_id
                    JOIN schedules s ON s.id = sr.schedule_id
                    WHERE DATE(s.departure_time) = daily_schedules.date
                    AND b.payment_status = "PAID"
                    ' . ($busId ? 'AND s.bus_id = ' . $busId : '') . '
                    ' . ($routeId ? 'AND sr.route_id = ' . $routeId : '') . '
                ) as passengers'),
                DB::raw('(
                    SELECT SUM(b.final_price)
                    FROM bookings b
                    JOIN schedule_rute sr ON sr.id = b.schedule_id
                    JOIN schedules s ON s.id = sr.schedule_id
                    WHERE DATE(s.departure_time) = daily_schedules.date
                    AND b.payment_status = "PAID"
                    ' . ($busId ? 'AND s.bus_id = ' . $busId : '') . '
                    ' . ($routeId ? 'AND sr.route_id = ' . $routeId : '') . '
                ) as revenue'),
                DB::raw('(
                    SELECT SUM(u.total_aktual_harga_bbm)
                    FROM utility_bbm u
                    JOIN schedules s ON s.id = u.schedule_id
                    WHERE DATE(s.departure_time) = daily_schedules.date
                    ' . ($busId ? 'AND s.bus_id = ' . $busId : '') . '
                ) as fuel')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

            // Operational Table Data
            $operationalQuery = Buses::select([
                'buses.id',
                'buses.bus_number',
                'classes.class_name as bus_class',
                DB::raw('COUNT(DISTINCT schedules.id) as total_trips'),
                DB::raw('(
                    SELECT COUNT(DISTINCT b2.id)
                    FROM bookings b2
                    JOIN schedule_rute sr2 ON sr2.id = b2.schedule_id
                    JOIN schedules s2 ON s2.id = sr2.schedule_id
                    WHERE s2.bus_id = buses.id
                    AND b2.payment_status = "PAID"
                    AND DATE(s2.departure_time) >= "' . $startDate->format('Y-m-d') . '"
                    AND DATE(s2.departure_time) <= "' . $endDate->format('Y-m-d') . '"
                    ' . ($routeId ? 'AND sr2.route_id = ' . $routeId : '') . '
                ) as total_passengers'),
                DB::raw('(
                    SELECT SUM(u2.total_aktual_harga_bbm)
                    FROM utility_bbm u2
                    JOIN schedules s2 ON s2.id = u2.schedule_id
                    WHERE s2.bus_id = buses.id
                    AND DATE(s2.departure_time) >= "' . $startDate->format('Y-m-d') . '"
                    AND DATE(s2.departure_time) <= "' . $endDate->format('Y-m-d') . '"
                ) as fuel_cost'),
                DB::raw('(
                    SELECT SUM(b2.final_price)
                    FROM bookings b2
                    JOIN schedule_rute sr2 ON sr2.id = b2.schedule_id
                    JOIN schedules s2 ON s2.id = sr2.schedule_id
                    WHERE s2.bus_id = buses.id
                    AND b2.payment_status = "PAID"
                    AND DATE(s2.departure_time) >= "' . $startDate->format('Y-m-d') . '"
                    AND DATE(s2.departure_time) <= "' . $endDate->format('Y-m-d') . '"
                    ' . ($routeId ? 'AND sr2.route_id = ' . $routeId : '') . '
                    GROUP BY s2.bus_id
                ) as revenue'),
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
            });

            // Filter untuk route_id
            if ($routeId) {
                $operationalQuery->whereExists(function ($query) use ($routeId, $startDate, $endDate) {
                    $query->select(DB::raw(1))
                        ->from('schedules as s')
                        ->join('schedule_rute as sr', 'sr.schedule_id', '=', 's.id')
                        ->whereRaw('s.bus_id = buses.id')
                        ->where('sr.route_id', $routeId)
                        ->whereDate('s.departure_time', '>=', $startDate)
                        ->whereDate('s.departure_time', '<=', $endDate);
                });
            }

            if ($busId) {
                $operationalQuery->where('buses.id', $busId);
            }
            
            if ($classId) {
                $operationalQuery->where('buses.class_id', $classId);
            }

            if (!empty($search)) {
                $operationalQuery->where(function($q) use ($search) {
                    $q->where('buses.bus_number', 'like', "%{$search}%")
                      ->orWhere('classes.class_name', 'like', "%{$search}%");
                });
            }

            $operationalQuery->groupBy('buses.id', 'buses.bus_number', 'classes.class_name');

            switch ($sortBy) {
                case 'bus_number':
                    $operationalQuery->orderBy('buses.bus_number', $sortDir);
                    break;
                case 'bus_class':
                    $operationalQuery->orderBy('classes.class_name', $sortDir);
                    break;
                case 'total_trips':
                    $operationalQuery->orderBy('total_trips', $sortDir);
                    break;
                case 'total_passengers':
                    $operationalQuery->orderBy('total_passengers', $sortDir);
                    break;
                case 'fuel_cost':
                    $operationalQuery->orderBy('fuel_cost', $sortDir);
                    break;
                case 'revenue':
                    $operationalQuery->orderBy('revenue', $sortDir);
                    break;
                default:
                    $operationalQuery->orderBy('buses.bus_number', 'asc');
            }

            $operationalResult = $operationalQuery->paginate($perPage);

            foreach ($operationalResult->items() as $bus) {
                $routeQuery = DB::table('schedules')
                    ->select(DB::raw("CONCAT(start_loc.name, ' - ', end_loc.name) as route"))
                    ->join('schedule_rute', 'schedule_rute.schedule_id', '=', 'schedules.id')
                    ->join('routes', 'schedule_rute.route_id', '=', 'routes.id')
                    ->join('locations as start_loc', 'routes.start_location_id', '=', 'start_loc.id')
                    ->join('locations as end_loc', 'routes.end_location_id', '=', 'end_loc.id')
                    ->where('schedules.bus_id', $bus->id)
                    ->whereDate('schedules.departure_time', '>=', $startDate)
                    ->whereDate('schedules.departure_time', '<=', $endDate);

                if ($routeId) {
                    $routeQuery->where('routes.id', $routeId);
                }

                $routes = $routeQuery->groupBy('route')
                    ->pluck('route')
                    ->first();

                $bus->route = $routes ?? '-';
                $bus->fuel_consumption = $bus->fuel_cost ? round($bus->fuel_cost / 15000) : 0;
                $bus->fuel_cost = (int)$bus->fuel_cost;
                $bus->revenue = (int)$bus->revenue;
            }

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
                    ],
                    'operational' => [
                        'data' => $operationalResult->items(),
                        'meta' => [
                            'current_page' => $operationalResult->currentPage(),
                            'per_page' => $operationalResult->perPage(),
                            'total_items' => $operationalResult->total(),
                            'total_pages' => $operationalResult->lastPage()
                        ]
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data dashboard',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 
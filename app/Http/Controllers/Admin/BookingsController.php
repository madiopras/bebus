<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Bookings;
use App\Models\MidtransLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class BookingsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['user_id', 'schedule_id', 'booking_date', 'payment_status', 'final_price', 'voucher_id', 'specialdays_id', 'description', 'created_by_id', 'updated_by_id']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $bookings = Bookings::filter($filters)->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'data' => $bookings->items(),
                'current_page' => $bookings->currentPage(),
                'total_pages' => $bookings->lastPage(),
                'total_items' => $bookings->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch bookings', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $booking = Bookings::findOrFail($id);

            return response()->json($booking, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch booking', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreBookingRequest $request)
    {
        try {
            $customerType = $request->user()->hasRole('user') ? 'CUSTOMER' : 'ADMIN';

            $booking = Bookings::create([
                'user_id' => $request->user_id,
                'schedule_id' => $request->schedule_id,
                'booking_date' => $request->booking_date,
                'payment_status' => $request->payment_status,
                'final_price' => $request->final_price,
                'voucher_id' => $request->voucher_id,
                'specialdays_id' => $request->specialdays_id,
                'description' => $request->description,
                'created_by_id' => $request->user()->id,
                'updated_by_id' => $request->user()->id,
                'customer_type' => $customerType,
            ]);

            return response()->json(['message' => 'Booking created successfully', 'booking' => $booking], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create booking', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateBookingRequest $request, $id)
    {
        $booking = Bookings::find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        try {
            $customerType = $request->user()->hasRole('user') ? 'CUSTOMER' : 'ADMIN';

            $booking->update(array_merge(
                $request->only(['user_id', 'schedule_id', 'booking_date', 'payment_status', 'final_price', 'voucher_id', 'specialdays_id', 'description']),
                ['customer_type' => $customerType]
            ));

            $booking->updated_by_id = $request->user()->id;
            $booking->save();

            return response()->json(['message' => 'Booking updated successfully', 'booking' => $booking], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update booking', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $booking = Bookings::find($id);

            if (!$booking) {
                return response()->json(['message' => 'Booking not found'], 404);
            }

            $booking->delete();

            return response()->json(['message' => 'Booking deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete booking', 'error' => $e->getMessage()], 500);
        }
    }

    public function checkPaymentStatus($paymentId)
    {
        try {
            // Ambil booking ID dari payment ID (format: BOOK-{id}-timestamp)
            preg_match('/BOOK-(\d+)-/', $paymentId, $matches);
            if (empty($matches[1])) {
                return response()->json([
                    'status' => false,
                    'message' => 'Format payment ID tidak valid'
                ], 400);
            }

            $bookingId = $matches[1];
            
            // Ambil data booking beserta relasinya
            $booking = Bookings::select(
                'bookings.*',
                'sr.departure_time',
                'sr.arrival_time',
                'sr.price_rute',
                'l.name as dari',
                'l2.name as ke',
                'b.bus_number as kode_bus',
                'b.bus_name as nama_bus',
                'b.type_bus as tipe_bus'
            )
            ->leftJoin('schedule_rute as sr', 'bookings.schedule_id', '=', 'sr.id')
            ->leftJoin('routes as r', 'sr.route_id', '=', 'r.id')
            ->leftJoin('locations as l', 'r.start_location_id', '=', 'l.id')
            ->leftJoin('locations as l2', 'r.end_location_id', '=', 'l2.id')
            ->leftJoin('schedules as s', 'sr.schedule_id', '=', 's.id')
            ->leftJoin('buses as b', 's.bus_id', '=', 'b.id')
            ->with(['passengers'])
            ->findOrFail($bookingId);

            // Format response data
            $formattedData = [
                'booking' => [
                    'payment_id' => $paymentId,
                    'name' => $booking->name,
                    'email' => $booking->email,
                    'phone_number' => $booking->phone_number,
                    'booking_date' => $booking->booking_date,
                    'payment_status' => $booking->payment_status,
                    'final_price' => $booking->final_price,
                    'customer_type' => $booking->customer_type,
                    'redirect_url' => $booking->redirect_url
                ],
                'schedule_info' => [
                    'route' => $booking->dari && $booking->ke ? $booking->dari . ' - ' . $booking->ke : 'N/A',
                    'bus_info' => [
                        'kode' => $booking->kode_bus ?? 'N/A',
                        'nama' => $booking->nama_bus ?? 'N/A',
                        'tipe' => $booking->tipe_bus ?? 'N/A'
                    ],
                    'departure_time' => $booking->departure_time ?? 'N/A',
                    'arrival_time' => $booking->arrival_time ?? 'N/A',
                    'price_rute' => $booking->price_rute ?? 'N/A'
                ],
                'passengers' => $booking->passengers->map(function($passenger) {
                    return [
                        'name' => $passenger->name,
                        'gender' => $passenger->gender,
                        'phone_number' => $passenger->phone_number,
                        'schedule_seat_id' => $passenger->schedule_seat_id
                    ];
                })
            ];

            return response()->json([
                'status' => true,
                'data' => $formattedData
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data pembayaran',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBookingsThreeDays()
    {
        try {
            $threeDaysFromNow = now()->addDays(3);
            
            $bookings = Bookings::with(['passengers'])
                ->join('schedule_rute', 'bookings.schedule_id', '=', 'schedule_rute.id')
                ->join('schedules', 'schedule_rute.schedule_id', '=', 'schedules.id')
                ->join('buses', 'schedules.bus_id', '=', 'buses.id')
                ->join('classes', 'buses.class_id', '=', 'classes.id')
                ->join('routes', 'schedule_rute.route_id', '=', 'routes.id')
                ->join('locations as l1', 'routes.start_location_id', '=', 'l1.id')
                ->join('locations as l2', 'routes.end_location_id', '=', 'l2.id')
                ->where('schedule_rute.departure_time', '>', $threeDaysFromNow)
                ->where('bookings.payment_status', 'PAID')
                ->select(
                    'bookings.*', 
                    'schedule_rute.departure_time',
                    'schedule_rute.arrival_time',
                    'schedule_rute.price_rute',
                    'buses.bus_name',
                    'buses.bus_number',
                    'classes.class_name',
                    'l1.name as start_location',
                    'l2.name as end_location'
                )
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil mendapatkan data booking 3 hari kedepan',
                'data' => $bookings->map(function($booking) {
                    return [
                        'booking_id' => $booking->id,
                        'customer_name' => $booking->name,
                        'phone_number' => $booking->phone_number,
                        'email' => $booking->email,
                        'departure_time' => $booking->departure_time,
                        'arrival_time' => $booking->arrival_time,
                        'payment_id' => $booking->payment_id,
                        'price' => $booking->final_price,
                        'bus_info' => [
                            'name' => $booking->bus_name,
                            'number' => $booking->bus_number,
                            'class' => $booking->class_name
                        ],
                        'route' => $booking->start_location . ' - ' . $booking->end_location,
                        'passengers' => $booking->passengers->map(function($passenger) {
                            return [
                                'name' => $passenger->name,
                                'seat_number' => $passenger->schedule_seat_id
                            ];
                        })
                    ];
                })
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get Bookings Three Days Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Gagal mendapatkan data booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBookingsOneDay()
    {
        try {
            $oneDayFromNow = now()->addDay();
            
            $bookings = Bookings::with(['passengers'])
                ->join('schedule_rute', 'bookings.schedule_id', '=', 'schedule_rute.id')
                ->join('schedules', 'schedule_rute.schedule_id', '=', 'schedules.id')
                ->join('buses', 'schedules.bus_id', '=', 'buses.id')
                ->join('classes', 'buses.class_id', '=', 'classes.id')
                ->join('routes', 'schedule_rute.route_id', '=', 'routes.id')
                ->join('locations as l1', 'routes.start_location_id', '=', 'l1.id')
                ->join('locations as l2', 'routes.end_location_id', '=', 'l2.id')
                ->where('schedule_rute.departure_time', '>', $oneDayFromNow)
                ->where('bookings.payment_status', 'PAID')
                ->select(
                    'bookings.*', 
                    'schedule_rute.departure_time',
                    'schedule_rute.arrival_time',
                    'schedule_rute.price_rute',
                    'buses.bus_name',
                    'buses.bus_number',
                    'classes.class_name',
                    'l1.name as start_location',
                    'l2.name as end_location'
                )
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil mendapatkan data booking 1 hari kedepan',
                'data' => $bookings->map(function($booking) {
                    return [
                        'booking_id' => $booking->id,
                        'customer_name' => $booking->name,
                        'phone_number' => $booking->phone_number,
                        'email' => $booking->email,
                        'departure_time' => $booking->departure_time,
                        'arrival_time' => $booking->arrival_time,
                        'payment_id' => $booking->payment_id,
                        'price' => $booking->final_price,
                        'bus_info' => [
                            'name' => $booking->bus_name,
                            'number' => $booking->bus_number,
                            'class' => $booking->class_name
                        ],
                        'route' => $booking->start_location . ' - ' . $booking->end_location,
                        'passengers' => $booking->passengers->map(function($passenger) {
                            return [
                                'name' => $passenger->name,
                                'seat_number' => $passenger->schedule_seat_id
                            ];
                        })
                    ];
                })
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get Bookings One Day Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Gagal mendapatkan data booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getBookingsByClass(Request $request)
    {
        try {
            $bookingId = $request->query('booking_id');
            if (!$bookingId) {
                return response()->json([
                    'status' => false,
                    'message' => 'Booking ID harus disediakan'
                ], 400);
            }

            $oneDayFromNow = now()->addDay();
            $thirtyDaysFromNow = now()->addDays(30);

            // Dapatkan route_id dari booking yang dimaksud
            $referenceBooking = Bookings::join('schedule_rute', 'bookings.schedule_id', '=', 'schedule_rute.id')
                ->select('schedule_rute.route_id')
                ->where('bookings.id', $bookingId)
                ->firstOrFail();

            // Dapatkan semua jadwal rute dengan rute yang sama
            $schedules = \App\Models\ScheduleRute::query()
                ->join('schedules', 'schedule_rute.schedule_id', '=', 'schedules.id')
                ->join('buses', 'schedules.bus_id', '=', 'buses.id')
                ->join('classes', 'buses.class_id', '=', 'classes.id')
                ->join('routes', 'schedule_rute.route_id', '=', 'routes.id')
                ->join('locations as l1', 'routes.start_location_id', '=', 'l1.id')
                ->join('locations as l2', 'routes.end_location_id', '=', 'l2.id')
                ->where('schedule_rute.route_id', $referenceBooking->route_id)
                ->where('schedule_rute.is_active', true)
                ->whereBetween('schedule_rute.departure_time', [$oneDayFromNow, $thirtyDaysFromNow])
                ->select(
                    'schedule_rute.id',
                    'schedule_rute.departure_time',
                    'schedule_rute.arrival_time',
                    'schedule_rute.price_rute',
                    'buses.bus_name',
                    'buses.bus_number',
                    'classes.class_name',
                    'l1.name as start_location',
                    'l2.name as end_location'
                )
                ->get();

            return response()->json([
                'status' => true,
                'message' => 'Berhasil mendapatkan jadwal dengan rute yang sama',
                'data' => $schedules->map(function($schedule) {
                    return [
                        'schedule_id' => $schedule->id,
                        'departure_time' => $schedule->departure_time,
                        'arrival_time' => $schedule->arrival_time,
                        'price' => $schedule->price_rute,
                        'bus_info' => [
                            'name' => $schedule->bus_name,
                            'number' => $schedule->bus_number,
                            'class' => $schedule->class_name
                        ],
                        'route' => $schedule->start_location . ' - ' . $schedule->end_location
                    ];
                })
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Booking tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mendapatkan data jadwal',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reschedule;
use App\Models\Bookings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RescheduleController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Reschedule::query()
                ->join('bookings as prev_booking', 'reschedules.booking_previous_id', '=', 'prev_booking.id')
                ->join('bookings as new_booking', 'reschedules.booking_new_id', '=', 'new_booking.id')
                ->join('schedule_rute as prev_schedule', 'prev_booking.schedule_id', '=', 'prev_schedule.id')
                ->join('schedule_rute as new_schedule', 'reschedules.schedule_rute_id', '=', 'new_schedule.id')
                ->join('routes as new_route', 'new_schedule.route_id', '=', 'new_route.id')
                ->join('locations as l1', 'new_route.start_location_id', '=', 'l1.id')
                ->join('locations as l2', 'new_route.end_location_id', '=', 'l2.id')
                ->select(
                    'reschedules.*', 
                    'prev_booking.name as prev_customer_name',
                    'prev_booking.phone_number as prev_customer_phone',
                    'prev_booking.payment_id',
                    'new_booking.name as new_customer_name',
                    'new_booking.phone_number as new_customer_phone',
                    'prev_schedule.departure_time as prev_departure_time',
                    'new_schedule.departure_time as new_departure_time',
                    'l1.name as start_location',
                    'l2.name as end_location'
                );

            // Filter by customer name
            if ($request->has('customer_name')) {
                $query->where('prev_booking.name', 'like', '%' . $request->customer_name . '%');
            }

            // Filter by phone number
            if ($request->has('phone_number')) {
                $query->where('prev_booking.phone_number', 'like', '%' . $request->phone_number . '%');
            }

            // Filter by route
            if ($request->has('route')) {
                $route = $request->route;
                $query->where(function($q) use ($route) {
                    $q->where('l1.name', 'like', '%' . $route . '%')
                      ->orWhere('l2.name', 'like', '%' . $route . '%');
                });
            }

            // Filter by payment_id
            if ($request->has('payment_id')) {
                $query->where('prev_booking.payment_id', 'like', '%' . $request->payment_id . '%');
            }

            // Filter by date if provided
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('reschedules.created_at', [
                    $request->start_date,
                    $request->end_date
                ]);
            }

            // Add pagination
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);
            $reschedules = $query->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'message' => 'Berhasil mendapatkan data reschedule',
                'data' => $reschedules->map(function($reschedule) {
                    return [
                        'id' => $reschedule->id,
                        'booking_previous_id' => $reschedule->booking_previous_id,
                        'booking_new_id' => $reschedule->booking_new_id,
                        'prev_customer' => [
                            'name' => $reschedule->prev_customer_name,
                            'phone' => $reschedule->prev_customer_phone,
                            'departure_time' => $reschedule->prev_departure_time,
                            'payment_id' => $reschedule->payment_id
                        ],
                        'new_customer' => [
                            'name' => $reschedule->new_customer_name,
                            'phone' => $reschedule->new_customer_phone,
                            'departure_time' => $reschedule->new_departure_time,
                            'route' => $reschedule->start_location . ' - ' . $reschedule->end_location
                        ],
                        'schedule_rute_id' => $reschedule->schedule_rute_id,
                        'harga_baru' => $reschedule->harga_baru,
                        'alasan' => $reschedule->alasan,
                        'created_at' => $reschedule->created_at
                    ];
                }),
                'current_page' => $reschedules->currentPage(),
                'total_pages' => $reschedules->lastPage(),
                'total_items' => $reschedules->total()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mendapatkan data reschedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'booking_id' => 'required|exists:bookings,id',
                'new_schedule_id' => 'required|exists:schedule_rute,id',
                'alasan' => 'required|string',
                'biaya_reschedule' => 'required|numeric|min:0',
                'seats' => 'required|string'
            ]);

            // Get previous booking data
            $previousBooking = Bookings::with(['passengers'])->findOrFail($validated['booking_id']);
            
            // Get new schedule data
            $newSchedule = \App\Models\ScheduleRute::findOrFail($validated['new_schedule_id']);
            
            // Calculate total price based on number of passengers
            $numberOfPassengers = count($previousBooking->passengers);
            $totalPrice = $newSchedule->price_rute * $numberOfPassengers;
            
            // Create new booking with updated schedule
            $newBooking = Bookings::create([
                'user_id' => $request->user()->id,
                'name' => $previousBooking->name,
                'email' => $previousBooking->email,
                'phone_number' => $previousBooking->phone_number,
                'schedule_id' => $validated['new_schedule_id'],
                'booking_date' => now(),
                'payment_id' => $previousBooking->payment_id,
                'payment_status' => 'PAID',
                'final_price' => $totalPrice,
                'description' => 'Hasil reschedule dari booking #' . $previousBooking->id,
                'customer_type' => $previousBooking->customer_type,
                'created_by_id' => $request->user()->id,
                'updated_by_id' => $request->user()->id
            ]);

            // Process seats for new booking
            $seatArray = explode(',', $validated['seats']);
            foreach ($previousBooking->passengers as $index => $passenger) {
                if (isset($seatArray[$index])) {
                    // Create new passenger with new seat
                    $newBooking->passengers()->create([
                        'name' => $passenger->name,
                        'gender' => $passenger->gender,
                        'phone_number' => $passenger->phone_number,
                        'schedule_seat_id' => trim($seatArray[$index]),
                        'created_by_id' => $request->user()->id,
                        'updated_by_id' => $request->user()->id
                    ]);
                }
            }

            // Create reschedule record
            $reschedule = Reschedule::create([
                'booking_previous_id' => $validated['booking_id'],
                'booking_new_id' => $newBooking->id,
                'schedule_rute_id' => $validated['new_schedule_id'],
                'harga_baru' => $validated['biaya_reschedule'],
                'alasan' => $validated['alasan'],
                'created_by_id' => $request->user()->id,
                'updated_by_id' => $request->user()->id
            ]);

            // Update previous booking status
            $previousBooking->update([
                'payment_status' => 'CANCELLED',
                'description' => 'Dibatalkan untuk reschedule ke booking #' . $newBooking->id . ': ' . $validated['alasan'],
                'updated_by_id' => $request->user()->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Berhasil membuat reschedule',
                'data' => [
                    'reschedule_id' => $reschedule->id,
                    'previous_booking' => [
                        'id' => $previousBooking->id,
                        'status' => 'CANCELLED'
                    ],
                    'new_booking' => [
                        'id' => $newBooking->id,
                        'schedule_id' => $validated['new_schedule_id'],
                        'price' => $validated['biaya_reschedule'],
                        'seats' => $seatArray
                    ]
                ]
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat reschedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $reschedule = Reschedule::join('bookings as prev_booking', 'reschedules.booking_previous_id', '=', 'prev_booking.id')
                ->join('bookings as new_booking', 'reschedules.booking_new_id', '=', 'new_booking.id')
                ->join('schedule_rute as prev_schedule', 'prev_booking.schedule_id', '=', 'prev_schedule.id')
                ->join('schedule_rute as new_schedule', 'reschedules.schedule_rute_id', '=', 'new_schedule.id')
                ->join('schedules as prev_sch', 'prev_schedule.schedule_id', '=', 'prev_sch.id')
                ->join('schedules as new_sch', 'new_schedule.schedule_id', '=', 'new_sch.id')
                ->join('buses as prev_bus', 'prev_sch.bus_id', '=', 'prev_bus.id')
                ->join('buses as new_bus', 'new_sch.bus_id', '=', 'new_bus.id')
                ->join('classes as prev_class', 'prev_bus.class_id', '=', 'prev_class.id')
                ->join('classes as new_class', 'new_bus.class_id', '=', 'new_class.id')
                ->join('routes as prev_route', 'prev_schedule.route_id', '=', 'prev_route.id')
                ->join('routes as new_route', 'new_schedule.route_id', '=', 'new_route.id')
                ->join('locations as prev_l1', 'prev_route.start_location_id', '=', 'prev_l1.id')
                ->join('locations as prev_l2', 'prev_route.end_location_id', '=', 'prev_l2.id')
                ->join('locations as new_l1', 'new_route.start_location_id', '=', 'new_l1.id')
                ->join('locations as new_l2', 'new_route.end_location_id', '=', 'new_l2.id')
                ->select(
                    'reschedules.*',
                    'prev_booking.name',
                    'prev_booking.email',
                    'prev_booking.phone_number',
                    'prev_booking.final_price as prev_final_price',
                    'new_booking.final_price as new_final_price',
                    'prev_schedule.departure_time as prev_departure_time',
                    'prev_schedule.arrival_time as prev_arrival_time',
                    'prev_schedule.price_rute as prev_price_rute',
                    'new_schedule.departure_time as new_departure_time',
                    'new_schedule.arrival_time as new_arrival_time',
                    'new_schedule.price_rute as new_price_rute',
                    'prev_bus.bus_name as prev_bus_name',
                    'prev_bus.bus_number as prev_bus_number',
                    'prev_class.class_name as prev_class_name',
                    'new_bus.bus_name as new_bus_name',
                    'new_bus.bus_number as new_bus_number',
                    'new_class.class_name as new_class_name',
                    'prev_l1.name as prev_start_location',
                    'prev_l2.name as prev_end_location',
                    'new_l1.name as new_start_location',
                    'new_l2.name as new_end_location'
                )
                ->where('reschedules.id', $id)
                ->firstOrFail();

            // Get previous passengers data
            $previousPassengers = Bookings::find($reschedule->booking_previous_id)->passengers->map(function($passenger) {
                return [
                    'name' => $passenger->name,
                    'seat_number' => $passenger->schedule_seat_id
                ];
            });

            // Get new passengers data
            $newPassengers = Bookings::find($reschedule->booking_new_id)->passengers->map(function($passenger) {
                return [
                    'name' => $passenger->name,
                    'seat_number' => $passenger->schedule_seat_id
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Berhasil mendapatkan detail reschedule',
                'data' => [
                    'id' => $reschedule->id,
                    'booking_id' => $reschedule->booking_previous_id,
                    'customer' => [
                        'name' => $reschedule->name,
                        'email' => $reschedule->email,
                        'phone' => $reschedule->phone_number
                    ],
                    'previous_schedule' => [
                        'route' => $reschedule->prev_start_location . ' - ' . $reschedule->prev_end_location,
                        'bus_info' => [
                            'name' => $reschedule->prev_bus_name,
                            'number' => $reschedule->prev_bus_number,
                            'class' => $reschedule->prev_class_name
                        ],
                        'departure_time' => $reschedule->prev_departure_time,
                        'arrival_time' => $reschedule->prev_arrival_time,
                        'price_per_seat' => $reschedule->prev_price_rute,
                        'final_price' => $reschedule->prev_final_price,
                        'passengers' => $previousPassengers
                    ],
                    'new_schedule' => [
                        'route' => $reschedule->new_start_location . ' - ' . $reschedule->new_end_location,
                        'bus_info' => [
                            'name' => $reschedule->new_bus_name,
                            'number' => $reschedule->new_bus_number,
                            'class' => $reschedule->new_class_name
                        ],
                        'departure_time' => $reschedule->new_departure_time,
                        'arrival_time' => $reschedule->new_arrival_time,
                        'price_per_seat' => $reschedule->new_price_rute,
                        'final_price' => $reschedule->new_final_price,
                        'passengers' => $newPassengers
                    ],
                    'reschedule_info' => [
                        'alasan' => $reschedule->alasan,
                        'biaya_reschedule' => $reschedule->harga_baru,
                        'created_at' => $reschedule->created_at
                    ]
                ]
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Data reschedule tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mendapatkan detail reschedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $reschedule = Reschedule::findOrFail($id);

            $validated = $request->validate([
                'schedule_rute_id' => 'required|exists:schedule_rute,id',
                'harga_baru' => 'required|numeric|min:0',
                'alasan' => 'required|string'
            ]);

            // Update reschedule record
            $reschedule->update([
                'schedule_rute_id' => $validated['schedule_rute_id'],
                'harga_baru' => $validated['harga_baru'],
                'alasan' => $validated['alasan']
            ]);

            // Update new booking schedule
            $reschedule->newBooking->update([
                'schedule_id' => $validated['schedule_rute_id'],
                'final_price' => $validated['harga_baru'],
                'description' => 'Reschedule diperbarui: ' . $validated['alasan']
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Reschedule berhasil diperbarui',
                'data' => [
                    'id' => $reschedule->id,
                    'booking_previous_id' => $reschedule->booking_previous_id,
                    'booking_new_id' => $reschedule->booking_new_id,
                    'schedule_rute_id' => $reschedule->schedule_rute_id,
                    'harga_baru' => $reschedule->harga_baru,
                    'alasan' => $reschedule->alasan,
                    'created_at' => $reschedule->created_at
                ]
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Data reschedule tidak ditemukan'
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui reschedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $reschedule = Reschedule::findOrFail($id);
            $reschedule->delete();

            return response()->json([
                'status' => true,
                'message' => 'Reschedule berhasil dihapus'
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Data reschedule tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus reschedule',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
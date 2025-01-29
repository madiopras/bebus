<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Refund;
use App\Models\Bookings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Validator;

class RefundController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Refund::query()
                ->join('bookings', 'refunds.booking_id', '=', 'bookings.id')
                ->select(
                    'refunds.*',
                    'bookings.name as customer_name',
                    'bookings.phone_number',
                    'bookings.payment_id'
                );

            // Filter by customer name
            if ($request->has('customer_name')) {
                $query->where('bookings.name', 'like', '%' . $request->customer_name . '%');
            }

            // Filter by phone number
            if ($request->has('phone_number')) {
                $query->where('bookings.phone_number', 'like', '%' . $request->phone_number . '%');
            }

            // Filter by date range
            if ($request->has('created_at_start') && $request->has('created_at_end')) {
                $query->whereBetween('refunds.created_at', [
                    $request->created_at_start,
                    $request->created_at_end
                ]);
            }

            // Add pagination
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);
            $refunds = $query->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'message' => 'Berhasil mendapatkan data refund',
                'data' => $refunds->map(function($refund) {
                    return [
                        'id' => $refund->id,
                        'booking_id' => $refund->booking_id,
                        'customer_name' => $refund->customer_name,
                        'phone_number' => $refund->phone_number,
                        'payment_id' => $refund->payment_id,
                        'alasan' => $refund->alasan,
                        'persentase' => $refund->persentase,
                        'estimasi_refund' => $refund->estimasi_refund,
                        'created_at' => $refund->created_at
                    ];
                }),
                'current_page' => $refunds->currentPage(),
                'total_pages' => $refunds->lastPage(),
                'total_items' => $refunds->total()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mendapatkan data refund',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'booking_id' => 'required|exists:bookings,id',
                'persentase' => 'required|numeric|min:0|max:100',
                'alasan' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $validated = $validator->validated();

            // Get booking data
            $booking = Bookings::findOrFail($validated['booking_id']);

            // Hitung estimasi refund (100% - persentase potongan)
            $persentaseRefund = 100 - $validated['persentase'];
            $estimasiRefund = ($persentaseRefund / 100) * $booking->final_price;

            // Create refund record
            $refund = Refund::create([
                'booking_id' => $validated['booking_id'],
                'persentase' => $validated['persentase'],
                'alasan' => $validated['alasan'],
                'estimasi_refund' => $estimasiRefund
            ]);

            // Update booking status
            $booking->update([
                'payment_status' => 'CANCELLED',
                'description' => 'Dibatalkan: ' . $validated['alasan']
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Refund berhasil dibuat',
                'data' => $refund
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat refund',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $refund = Refund::join('bookings', 'refunds.booking_id', '=', 'bookings.id')
                ->join('schedule_rute', 'bookings.schedule_id', '=', 'schedule_rute.id')
                ->join('schedules', 'schedule_rute.schedule_id', '=', 'schedules.id')
                ->join('buses', 'schedules.bus_id', '=', 'buses.id')
                ->join('classes', 'buses.class_id', '=', 'classes.id')
                ->join('routes', 'schedule_rute.route_id', '=', 'routes.id')
                ->join('locations as l1', 'routes.start_location_id', '=', 'l1.id')
                ->join('locations as l2', 'routes.end_location_id', '=', 'l2.id')
                ->select(
                    'refunds.*',
                    'bookings.name as customer_name',
                    'bookings.phone_number',
                    'bookings.email',
                    'bookings.payment_id',
                    'bookings.final_price',
                    'schedule_rute.departure_time',
                    'schedule_rute.arrival_time',
                    'schedule_rute.price_rute',
                    'buses.bus_name',
                    'buses.bus_number',
                    'classes.class_name',
                    'l1.name as start_location',
                    'l2.name as end_location'
                )
                ->where('refunds.id', $id)
                ->firstOrFail();

            // Get passengers data
            $passengers = Bookings::find($refund->booking_id)->passengers->map(function($passenger) {
                return [
                    'name' => $passenger->name,
                    'seat_number' => $passenger->schedule_seat_id
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Berhasil mendapatkan detail refund',
                'data' => [
                    'refund_info' => [
                        'id' => $refund->id,
                        'booking_id' => $refund->booking_id,
                        'tanggal' => $refund->tanggal,
                        'alasan' => $refund->alasan,
                        'estimasi_refund' => $refund->estimasi_refund,
                        'persentase' => $refund->persentase
                    ],
                    'booking_info' => [
                        'customer_name' => $refund->customer_name,
                        'phone_number' => $refund->phone_number,
                        'email' => $refund->email,
                        'payment_id' => $refund->payment_id,
                        'price' => $refund->final_price,
                        'departure_time' => $refund->departure_time,
                        'arrival_time' => $refund->arrival_time,
                        'bus_info' => [
                            'name' => $refund->bus_name,
                            'number' => $refund->bus_number,
                            'class' => $refund->class_name
                        ],
                        'route' => $refund->start_location . ' - ' . $refund->end_location,
                        'passengers' => $passengers
                    ]
                ]
            ], 200);

        } catch (ModelNotFoundException $e) {
            return response()->json([
                'status' => false,
                'message' => 'Data refund tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mendapatkan detail refund',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $refund = Refund::findOrFail($id);

            $validated = $request->validate([
                'alasan' => 'required|string',
                'persentase' => 'required|numeric|min:0|max:100'
            ]);

            // Hitung ulang estimasi refund
            $estimasiRefund = ($validated['persentase'] / 100) * $refund->booking->final_price;

            $refund->update([
                'alasan' => $validated['alasan'],
                'estimasi_refund' => $estimasiRefund,
                'persentase' => $validated['persentase']
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Refund berhasil diperbarui',
                'data' => $refund->load('booking')
            ], 200);

        } catch (\Exception $e) {
            Log::error('Refund Update Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui refund',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $refund = Refund::findOrFail($id);
            $refund->delete();

            return response()->json([
                'status' => true,
                'message' => 'Refund berhasil dihapus'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Refund Delete Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus refund',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 
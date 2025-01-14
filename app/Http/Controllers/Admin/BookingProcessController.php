<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bookings;
use App\Models\ScheduleRute;
use App\Models\Passenger;
use App\Models\Seats;
use App\Models\Schedules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Models\Payments;

class BookingProcessController extends Controller
{
    public function store(Request $request)
    {
        // Validasi request
        $validator = Validator::make($request->all(), [
            'schedule_rute_id' => 'required|exists:schedule_rute,id',
            'booker.name' => 'required|string|max:255',
            'booker.email' => 'required|email|max:255',
            'booker.phone' => 'required|string|max:20',
            'passengers' => 'required|array|min:1',
            'passengers.*.seat_number' => 'required|integer',
            'passengers.*.name' => 'required|string|max:255',
            'passengers.*.phone' => 'nullable|string|max:20',
            'passengers.*.gender' => 'required|in:L,P',
            'payment_method' => 'required|string|in:TUNAI,TRANSFER',
            'customer_type' => 'required|string|in:ADMIN,CUSTOMER'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Ambil data schedule_rute
            $scheduleRute = ScheduleRute::with('schedule')->findOrFail($request->schedule_rute_id);
            
            // Hitung total harga
            $totalPrice = count($request->passengers) * $scheduleRute->price_rute;

            // Buat booking baru
            $booking = Bookings::create([
                'user_id' => $request->user()->id,
                'schedule_id' => $request->schedule_rute_id,
                'name' => $request->booker['name'],
                'email' => $request->booker['email'],
                'phone_number' => $request->booker['phone'],
                'booking_date' => Carbon::now(),
                'payment_status' => 'UNPAID',
                'final_price' => $totalPrice,
                'created_by_id' => $request->user() ? $request->user()->id : null,
                'updated_by_id' => $request->user() ? $request->user()->id : null,
                'customer_type' => $request->customer_type
            ]);

            // Simpan data penumpang
            foreach ($request->passengers as $passenger) {
                // Ambil seat ID berdasarkan nomor kursi
                $seat = Seats::where('bus_id', $scheduleRute->schedule->bus_id)
                            ->where('seat_number', $passenger['seat_number'])
                            ->first();

                if (!$seat) {
                    throw new \Exception('Kursi tidak ditemukan: ' . $passenger['seat_number']);
                }

                // Cek apakah kursi sudah dipesan
                $existingPassenger = Passenger::where('schedule_seat_id', $seat->id)
                    ->whereHas('booking', function($query) use ($scheduleRute) {
                        $query->where('schedule_id', $scheduleRute->id)
                              ->where('payment_status', '!=', 'CANCELLED');
                    })->first();

                if ($existingPassenger) {
                    throw new \Exception('Kursi ' . $passenger['seat_number'] . ' sudah dipesan');
                }

                Passenger::create([
                    'booking_id' => $booking->id,
                    'schedule_seat_id' => $seat->id,
                    'name' => $passenger['name'],
                    'phone_number' => $passenger['phone'] ?? null,
                    'gender' => $passenger['gender'],
                    'created_by_id' => $request->user() ? $request->user()->id : null,
                    'updated_by_id' => $request->user() ? $request->user()->id : null
                ]);
            }

            // Jika metode pembayaran TUNAI, buat record payment
            if ($request->payment_method === 'TUNAI') {
                $payment = Payments::create([
                    'booking_id' => $booking->id,
                    'payment_method' => $request->payment_method,
                    'payment_date' => Carbon::now(),
                    'amount' => $totalPrice,
                    'created_by_id' => $request->user() ? $request->user()->id : null,
                    'updated_by_id' => $request->user() ? $request->user()->id : null
                ]);

                // Update payment_id dan status pembayaran pada booking
                $booking->update([
                    'payment_id' => $payment->id,
                    'payment_status' => 'PAID'
                ]);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Booking berhasil dibuat',
                'data' => $booking->load('passengers')
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 
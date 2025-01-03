<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['user_id', 'schedule_id', 'booking_date', 'payment_status', 'final_price', 'voucher_id', 'specialdays_id', 'description', 'created_by_id', 'updated_by_id']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $bookings = Booking::filter($filters)->paginate($limit, ['*'], 'page', $page);

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
            $booking = Booking::findOrFail($id);

            return response()->json($booking, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch booking', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreBookingRequest $request)
    {
        try {
            $booking = Booking::create([
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
            ]);

            return response()->json(['message' => 'Booking created successfully', 'booking' => $booking], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create booking', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateBookingRequest $request, $id)
    {
        $booking = Booking::find($id);

        if (!$booking) {
            return response()->json(['message' => 'Booking not found'], 404);
        }

        try {
            $booking->update($request->only(['user_id', 'schedule_id', 'booking_date', 'payment_status', 'final_price', 'voucher_id', 'specialdays_id', 'description']));

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
            $booking = Booking::find($id);

            if (!$booking) {
                return response()->json(['message' => 'Booking not found'], 404);
            }

            $booking->delete();

            return response()->json(['message' => 'Booking deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete booking', 'error' => $e->getMessage()], 500);
        }
    }
}

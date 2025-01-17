<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBookingRequest;
use App\Http\Requests\UpdateBookingRequest;
use App\Models\Bookings;
use Illuminate\Http\Request;

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
}

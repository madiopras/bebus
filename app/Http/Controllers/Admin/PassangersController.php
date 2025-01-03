<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePassengerRequest;
use App\Http\Requests\UpdatePassengerRequest;
use App\Models\Passenger;
use Illuminate\Http\Request;

class PassengersController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['booking_id', 'schedule_seat_id', 'name', 'phone_number', 'description', 'created_by_id', 'updated_by_id']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $passengers = Passenger::filter($filters)->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'data' => $passengers->items(),
                'current_page' => $passengers->currentPage(),
                'total_pages' => $passengers->lastPage(),
                'total_items' => $passengers->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch passengers', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $passenger = Passenger::findOrFail($id);

            return response()->json($passenger, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch passenger', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StorePassengerRequest $request)
    {
        try {
            $passenger = Passenger::create([
                'booking_id' => $request->booking_id,
                'schedule_seat_id' => $request->schedule_seat_id,
                'name' => $request->name,
                'phone_number' => $request->phone_number,
                'description' => $request->description,
                'created_by_id' => $request->user()->id,
                'updated_by_id' => $request->user()->id,
            ]);

            return response()->json(['message' => 'Passenger created successfully', 'passenger' => $passenger], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create passenger', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdatePassengerRequest $request, $id)
    {
        $passenger = Passenger::find($id);

        if (!$passenger) {
            return response()->json(['message' => 'Passenger not found'], 404);
        }

        try {
            $passenger->update($request->only(['booking_id', 'schedule_seat_id', 'name', 'phone_number', 'description']));

            $passenger->updated_by_id = $request->user()->id;
            $passenger->save();

            return response()->json(['message' => 'Passenger updated successfully', 'passenger' => $passenger], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update passenger', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $passenger = Passenger::find($id);

            if (!$passenger) {
                return response()->json(['message' => 'Passenger not found'], 404);
            }

            $passenger->delete();

            return response()->json(['message' => 'Passenger deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete passenger', 'error' => $e->getMessage()], 500);
        }
    }
}

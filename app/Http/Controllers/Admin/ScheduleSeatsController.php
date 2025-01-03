<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreScheduleSeatRequest;
use App\Http\Requests\UpdateScheduleSeatRequest;
use App\Models\ScheduleSeat;
use Illuminate\Http\Request;

class ScheduleSeatsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['schedule_id', 'seat_id', 'is_available', 'description', 'created_by_id', 'updated_by_id']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $scheduleSeats = ScheduleSeat::filter($filters)->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'data' => $scheduleSeats->items(),
                'current_page' => $scheduleSeats->currentPage(),
                'total_pages' => $scheduleSeats->lastPage(),
                'total_items' => $scheduleSeats->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch schedule seats', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $scheduleSeat = ScheduleSeat::findOrFail($id);

            return response()->json($scheduleSeat, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch schedule seat', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreScheduleSeatRequest $request)
    {
        try {
            $scheduleSeat = ScheduleSeat::create([
                'schedule_id' => $request->schedule_id,
                'seat_id' => $request->seat_id,
                'is_available' => $request->is_available,
                'description' => $request->description,
                'created_by_id' => $request->user()->id,
                'updated_by_id' => $request->user()->id,
            ]);

            return response()->json(['message' => 'Schedule seat created successfully', 'schedule_seat' => $scheduleSeat], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create schedule seat', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateScheduleSeatRequest $request, $id)
    {
        $scheduleSeat = ScheduleSeat::find($id);

        if (!$scheduleSeat) {
            return response()->json(['message' => 'Schedule seat not found'], 404);
        }

        try {
            $scheduleSeat->update($request->only(['schedule_id', 'seat_id', 'is_available', 'description']));

            $scheduleSeat->updated_by_id = $request->user()->id;
            $scheduleSeat->save();

            return response()->json(['message' => 'Schedule seat updated successfully', 'schedule_seat' => $scheduleSeat], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update schedule seat', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $scheduleSeat = ScheduleSeat::find($id);

            if (!$scheduleSeat) {
                return response()->json(['message' => 'Schedule seat not found'], 404);
            }

            $scheduleSeat->delete();

            return response()->json(['message' => 'Schedule seat deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete schedule seat', 'error' => $e->getMessage()], 500);
        }
    }
}

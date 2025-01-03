<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSeatRequest;
use App\Models\Seat;
use Illuminate\Http\Request;

class SeatsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['bus_id', 'seat_number', 'created_by_id', 'updated_by_id']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $seats = Seat::filter($filters)->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'data' => $seats->items(),
                'current_page' => $seats->currentPage(),
                'total_pages' => $seats->lastPage(),
                'total_items' => $seats->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch seats', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $seat = Seat::findOrFail($id);

            return response()->json($seat, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch seat', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreSeatRequest $request)
    {
        try {
            $seat = Seat::create([
                'bus_id' => $request->bus_id,
                'seat_number' => $request->seat_number,
                'created_by_id' => $request->user()->id,
                'updated_by_id' => $request->user()->id,
            ]);

            return response()->json(['message' => 'Seat created successfully', 'seat' => $seat], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create seat', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $seat = Seat::find($id);

        if (!$seat) {
            return response()->json(['message' => 'Seat not found'], 404);
        }

        try {
            $seat->update($request->only(['bus_id', 'seat_number']));

            $seat->updated_by_id = $request->user()->id;
            $seat->save();

            return response()->json(['message' => 'Seat updated successfully', 'seat' => $seat], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update seat', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $seat = Seat::find($id);

            if (!$seat) {
                return response()->json(['message' => 'Seat not found'], 404);
            }

            $seat->delete();

            return response()->json(['message' => 'Seat deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete seat', 'error' => $e->getMessage()], 500);
        }
    }
}

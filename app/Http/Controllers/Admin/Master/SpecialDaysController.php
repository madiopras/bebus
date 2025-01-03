<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\SpecialDays\StoreSpecialDaysRequest;
use App\Http\Requests\SpecialDays\UpdateSpecialDaysRequest;
use App\Models\SpecialDays;
use Illuminate\Http\Request;

class SpecialDaysController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['name', 'start_date', 'end_date', 'description', 'price_percentage', 'is_increase', 'is_active', 'created_by_id', 'updated_by_id']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $specialDays = SpecialDays::filter($filters)->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'data' => $specialDays->items(),
                'current_page' => $specialDays->currentPage(),
                'total_pages' => $specialDays->lastPage(),
                'total_items' => $specialDays->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch special days', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $specialDay = SpecialDays::findOrFail($id);

            return response()->json($specialDay, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch special day', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreSpecialDaysRequest $request)
    {
        try {
            $specialDay = SpecialDays::create([
                'name' => $request->name,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'description' => $request->description,
                'price_percentage' => $request->price_percentage,
                'is_increase' => $request->is_increase,
                'is_active' => $request->is_active,
                'created_by_id' => $request->user()->id,
                'updated_by_id' => $request->user()->id,
            ]);

            return response()->json(['message' => 'Special day created successfully', 'special_day' => $specialDay], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create special day', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateSpecialDaysRequest $request, $id)
    {
        $specialDay = SpecialDays::find($id);

        if (!$specialDay) {
            return response()->json(['message' => 'Special day not found'], 404);
        }

        try {
            $specialDay->update($request->only(['name', 'start_date', 'end_date', 'description', 'price_percentage', 'is_increase', 'is_active']));

            $specialDay->updated_by_id = $request->user()->id;
            $specialDay->save();

            return response()->json(['message' => 'Special day updated successfully', 'special_day' => $specialDay], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update special day', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $specialDay = SpecialDays::find($id);

            if (!$specialDay) {
                return response()->json(['message' => 'Special day not found'], 404);
            }

            $specialDay->delete();

            return response()->json(['message' => 'Special day deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete special day', 'error' => $e->getMessage()], 500);
        }
    }
}

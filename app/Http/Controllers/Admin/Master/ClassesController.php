<?php

// ClassesController.php
namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Classes\StoreClassesRequest;
use App\Models\Classes;
use Illuminate\Http\Request;

class ClassesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['class_name', 'description', 'has_ac', 'has_toilet', 'has_tv', 'has_music', 'has_air_mineral', 'has_wifi', 'has_snack']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $classes = Classes::filter($filters)->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'data' => $classes->items(),
                'current_page' => $classes->currentPage(),
                'total_pages' => $classes->lastPage(),
                'total_items' => $classes->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch classes', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $class = Classes::findOrFail($id);

            return response()->json($class, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch class', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreClassesRequest $request)
    {
        try {
            $class = Classes::create([
                'class_name' => $request->class_name,
                'description' => $request->description,
                'has_ac' => $request->has_ac,
                'has_toilet' => $request->has_toilet,
                'has_tv' => $request->has_tv,
                'has_music' => $request->has_music,
                'has_air_mineral' => $request->has_air_mineral,
                'has_wifi' => $request->has_wifi,
                'has_snack' => $request->has_snack,
                'created_by_id' => $request->user()->id,
                'updated_by_id' => $request->user()->id,
            ]);

            return response()->json(['message' => 'Class created successfully', 'class' => $class], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create class', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $class = Classes::find($id);

        if (!$class) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        try {
            $class->update($request->only(['class_name', 'description', 'has_ac', 'has_toilet', 'has_tv', 'has_music', 'has_air_mineral', 'has_wifi', 'has_snack']));

            $class->updated_by_id = $request->user()->id;
            $class->save();

            return response()->json(['message' => 'Class updated successfully', 'class' => $class], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update class', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $class = Classes::find($id);

            if (!$class) {
                return response()->json(['message' => 'Class not found'], 404);
            }

            $class->delete();

            return response()->json(['message' => 'Class deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete class', 'error' => $e->getMessage()], 500);
        }
    }
}

<?php

// ClassesController.php
namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Classes\StoreClassesRequest;
use App\Http\Requests\Classes\UpdateClassesRequest;
use App\Models\Classes;
use App\Models\Facility;
use Illuminate\Http\Request;

class ClassesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['class_name', 'description']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $classes = Classes::with('facilities:id,name')
                ->filter($filters)
                ->select('id', 'class_name', 'description')
                ->paginate($limit, ['*'], 'page', $page);

            // Ambil semua fasilitas yang tersedia
            $facilities = Facility::select('id', 'name')->get();

            return response()->json([
                'status' => true,
                'data' => $classes->items(),
                'facilities' => $facilities,
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
            $class = Classes::with('facilities:id,name')
                ->select('id', 'class_name', 'description')
                ->findOrFail($id);

            $facilities = Facility::select('id', 'name')->get();

            return response()->json([
                'class' => $class,
                'facilities' => $facilities
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch class', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreClassesRequest $request)
    {
        try {
            // 1. Menyimpan data ke tabel classes
            $class = Classes::create([
                'class_name' => $request->class_name,      // "MAHAL"
                'description' => $request->description,     // "paling mahal diantara mahal"
                'created_by_id' => $request->user()->id,   // ID user yang sedang login
                'updated_by_id' => $request->user()->id,   // ID user yang sedang login
            ]);

            // 2. Jika ada facilities yang dikirim, simpan ke class_facilities
            if ($request->has('facilities')) {
                foreach ($request->facilities as $facilityId) {  // [1]
                    // Menyimpan data ke tabel class_facilities untuk setiap facility
                    $class->facilities()->attach($facilityId, [
                        'created_by_id' => $request->user()->id,  // ID user yang sedang login
                        'updated_by_id' => $request->user()->id   // ID user yang sedang login
                    ]);
                    // Hasil di tabel class_facilities:
                    // class_id | facility_id | created_by_id | updated_by_id
                    // [new_id]|     1       |    user_id    |    user_id
                }
            }

            // 3. Load relasi facilities untuk ditampilkan di response
            $class->load('facilities:id,name');

            return response()->json([
                'message' => 'Class created successfully', 
                'class' => $class
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create class', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(UpdateClassesRequest $request, $id)
    {
        $class = Classes::find($id);

        if (!$class) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        try {
            $class->update($request->only(['class_name', 'description']));
            $class->updated_by_id = $request->user()->id;
            $class->save();

            // Update facilities if provided
            if ($request->has('facilities')) {
                $class->facilities()->sync(collect($request->facilities)->mapWithKeys(function ($facilityId) use ($request) {
                    return [$facilityId => [
                        'created_by_id' => $request->user()->id,
                        'updated_by_id' => $request->user()->id
                    ]];
                }));
            }

            $class->load('facilities:id,name');

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

            // Hapus relasi di class_facilities terlebih dahulu
            $class->facilities()->detach();
            $class->delete();

            return response()->json(['message' => 'Class deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete class', 'error' => $e->getMessage()], 500);
        }
    }
}

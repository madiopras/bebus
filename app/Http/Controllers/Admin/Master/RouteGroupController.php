<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\RouteGroup;
use App\Models\Locations;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RouteGroupController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['name', 'start_location_id', 'end_location_id', 'is_active']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $query = RouteGroup::with(['startLocation', 'endLocation'])
                ->filter($filters);

            $routeGroups = $query->paginate($limit, ['*'], 'page', $page);
            
            // Ambil semua lokasi untuk dropdown filter
            $locations = Locations::select('id', 'name')->get();

            return response()->json([
                'status' => true,
                'data' => $routeGroups->items(),
                'locations' => $locations,
                'current_page' => $routeGroups->currentPage(),
                'total_pages' => $routeGroups->lastPage(),
                'total_items' => $routeGroups->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data grup rute',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $routeGroup = RouteGroup::with(['startLocation', 'endLocation', 'routes'])->findOrFail($id);
            
            // Ambil semua lokasi untuk form edit
            $locations = Locations::select('id', 'name')->get();

            return response()->json([
                'status' => true,
                'data' => $routeGroup,
                'locations' => $locations
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Grup rute tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'start_location_id' => 'required|exists:locations,id',
                'end_location_id' => 'required|exists:locations,id|different:start_location_id',
                'time_difference' => 'required|integer|min:0',
                'description' => 'nullable|string',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Cek apakah grup rute dengan kombinasi lokasi yang sama sudah ada
            $existingGroup = RouteGroup::where('start_location_id', $request->start_location_id)
                                     ->where('end_location_id', $request->end_location_id)
                                     ->first();

            if ($existingGroup) {
                return response()->json([
                    'status' => false,
                    'message' => 'Grup rute dengan lokasi awal dan akhir tersebut sudah ada'
                ], 422);
            }

            $routeGroup = RouteGroup::create([
                'name' => $request->name,
                'start_location_id' => $request->start_location_id,
                'end_location_id' => $request->end_location_id,
                'time_difference' => $request->time_difference,
                'description' => $request->description,
                'is_active' => $request->is_active ?? true,
                'created_by_id' => $request->user()->id,
                'updated_by_id' => $request->user()->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Grup rute berhasil dibuat',
                'data' => $routeGroup
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat grup rute',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $routeGroup = RouteGroup::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'start_location_id' => 'required|exists:locations,id',
                'end_location_id' => 'required|exists:locations,id|different:start_location_id',
                'time_difference' => 'required|integer|min:0',
                'description' => 'nullable|string',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Cek apakah grup rute dengan kombinasi lokasi yang sama sudah ada (kecuali grup yang sedang diupdate)
            $existingGroup = RouteGroup::where('start_location_id', $request->start_location_id)
                                     ->where('end_location_id', $request->end_location_id)
                                     ->where('id', '!=', $id)
                                     ->first();

            if ($existingGroup) {
                return response()->json([
                    'status' => false,
                    'message' => 'Grup rute dengan lokasi awal dan akhir tersebut sudah ada'
                ], 422);
            }

            $routeGroup->update([
                'name' => $request->name,
                'start_location_id' => $request->start_location_id,
                'end_location_id' => $request->end_location_id,
                'time_difference' => $request->time_difference,
                'description' => $request->description,
                'is_active' => $request->is_active ?? $routeGroup->is_active,
                'updated_by_id' => $request->user()->id
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Grup rute berhasil diperbarui',
                'data' => $routeGroup
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal memperbarui grup rute',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    public function destroy($id)
    {
        try {
            $routeGroup = RouteGroup::findOrFail($id);

            // Cek apakah ada rute yang menggunakan grup ini
            if ($routeGroup->routes()->count() > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak dapat menghapus grup rute karena masih digunakan oleh beberapa rute'
                ], 422);
            }

            $routeGroup->delete();

            return response()->json([
                'status' => true,
                'message' => 'Grup rute berhasil dihapus'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal menghapus grup rute',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }

    public function getRouteList()
    {
        try {
            $routes = \App\Models\Routes::select(
                    'routes.id',
                    'start_loc.name as start_location',
                    'end_loc.name as end_location'
                )
                ->join('locations as start_loc', 'routes.start_location_id', '=', 'start_loc.id')
                ->join('locations as end_loc', 'routes.end_location_id', '=', 'end_loc.id')
                ->get()
                ->map(function($route) {
                    return [
                        'id' => $route->id,
                        'name' => $route->start_location . ' - ' . $route->end_location
                    ];
                });

            return response()->json([
                'status' => true,
                'data' => $routes
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil daftar rute',
                'error' => $e->getMessage()
            ], 500);
        }
    }
} 
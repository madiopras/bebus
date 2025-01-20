<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Routes\StoreRouteRequest;
use App\Http\Requests\Routes\UpdateRouteRequest;
use App\Models\Routes;
use App\Models\Locations;
use Illuminate\Http\Request;

class RoutesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['start_location', 'end_location', 'distance', 'price', 'start_location_id', 'end_location_id']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $query = Routes::query();

            // Filter berdasarkan start_location_id dan end_location_id
            if (isset($filters['start_location_id'])) {
                $query->where('start_location_id', $filters['start_location_id']);
            }
            if (isset($filters['end_location_id'])) {
                $query->where('end_location_id', $filters['end_location_id']);
            }

            // Filter yang sudah ada sebelumnya
            if (isset($filters['start_location'])) {
                $query->whereHas('startLocation', function($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['start_location'] . '%');
                });
            }
            if (isset($filters['end_location'])) {
                $query->whereHas('endLocation', function($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['end_location'] . '%');
                });
            }
            if (isset($filters['distance'])) {
                $query->where('distance', $filters['distance']);
            }
            if (isset($filters['price'])) {
                $query->where('price', $filters['price']);
            }

            $routes = $query->with(['startLocation', 'endLocation'])->paginate($limit, ['*'], 'page', $page);
            
            // Ambil semua lokasi yang tersedia
            $locations = Locations::select('id', 'name')->get();

            return response()->json([
                'status' => true,
                'data' => $routes->items(),
                'locations' => $locations,
                'current_page' => $routes->currentPage(),
                'total_pages' => $routes->lastPage(),
                'total_items' => $routes->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data rute',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $route = Routes::findOrFail($id);
            
            // Ambil semua lokasi yang tersedia
            $locations = Locations::select('id', 'name')->get();

            return response()->json([
                'route' => $route,
                'locations' => $locations
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch route', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreRouteRequest $request)
    {
        try {
            // Validasi lokasi awal dan akhir tidak boleh sama
            if ($request->start_location_id === $request->end_location_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Lokasi awal dan akhir tidak boleh sama'
                ], 422);
            }

            // Cek apakah rute dengan kombinasi lokasi yang sama sudah ada
            $existingRoute = Routes::where('start_location_id', $request->start_location_id)
                                 ->where('end_location_id', $request->end_location_id)
                                 ->first();

            if ($existingRoute) {
                return response()->json([
                    'status' => false,
                    'message' => 'Rute dengan lokasi awal dan akhir tersebut sudah ada'
                ], 422);
            }

            $route = Routes::create([
                'start_location_id' => $request->start_location_id,
                'end_location_id' => $request->end_location_id,
                'distance' => $request->distance,
                'price' => $request->price,
                'created_by_id' => $request->user()->id,
                'updated_by_id' => $request->user()->id,
            ]);

            return response()->json(['message' => 'Route created successfully', 'route' => $route], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create route', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateRouteRequest $request, $id)
    {
        $route = Routes::find($id);

        if (!$route) {
            return response()->json(['message' => 'Route not found'], 404);
        }

        try {
            // Validasi lokasi awal dan akhir tidak boleh sama
            if ($request->start_location_id === $request->end_location_id) {
                return response()->json([
                    'status' => false,
                    'message' => 'Lokasi awal dan akhir tidak boleh sama'
                ], 422);
            }

            // Cek apakah rute dengan kombinasi lokasi yang sama sudah ada (kecuali rute yang sedang diupdate)
            $existingRoute = Routes::where('start_location_id', $request->start_location_id)
                                 ->where('end_location_id', $request->end_location_id)
                                 ->where('id', '!=', $id)
                                 ->first();

            if ($existingRoute) {
                return response()->json([
                    'status' => false,
                    'message' => 'Rute dengan lokasi awal dan akhir tersebut sudah ada'
                ], 422);
            }

            $route->update($request->only(['start_location_id', 'end_location_id', 'distance', 'price']));

            $route->updated_by_id = $request->user()->id;
            $route->save();

            return response()->json(['message' => 'Route updated successfully', 'route' => $route], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update route', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $route = Routes::find($id);

            if (!$route) {
                return response()->json(['message' => 'Route not found'], 404);
            }

            $route->delete();

            return response()->json(['message' => 'Route deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete route', 'error' => $e->getMessage()], 500);
        }
    }
}

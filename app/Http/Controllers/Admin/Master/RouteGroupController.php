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
            $filters = $request->only(['name', 'is_active', 'route_id']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $query = RouteGroup::select('id', 'name', 'is_active');

            // Filter berdasarkan route_id jika ada
            if (isset($filters['route_id'])) {
                $query->whereHas('routes', function($q) use ($filters) {
                    $q->where('route_id', $filters['route_id']);
                });
            }

            // Filter lainnya
            if (isset($filters['name'])) {
                $query->where('name', 'like', '%' . $filters['name'] . '%');
            }

            if (isset($filters['is_active'])) {
                $query->where('is_active', $filters['is_active']);
            }

            $routeGroups = $query->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'data' => $routeGroups->items(),
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
            $routeGroup = RouteGroup::with(['routes.route'])->findOrFail($id);
            
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
                'description' => 'nullable|string',
                'is_active' => 'boolean',
                'routes' => 'required|array|min:1',
                'routes.*.route_id' => 'required|exists:routes,id',
                'routes.*.time_difference' => 'required|integer|min:15'
            ], [
                'routes.required' => 'Rute harus diisi',
                'routes.min' => 'Minimal harus memilih 1 rute',
                'routes.*.route_id.required' => 'ID Rute harus diisi',
                'routes.*.route_id.exists' => 'Rute tidak valid',
                'routes.*.time_difference.required' => 'Selisih waktu harus diisi',
                'routes.*.time_difference.min' => 'Selisih waktu minimal 15 menit'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();
            try {
                // Ambil lokasi awal dan akhir dari rute pertama
                $firstRoute = \App\Models\Routes::findOrFail($request->routes[0]['route_id']);
                
                $routeGroup = RouteGroup::create([
                    'name' => $request->name,
                    'description' => $request->description,
                    'is_active' => $request->is_active ?? true,
                    'created_by_id' => $request->user()->id,
                    'updated_by_id' => $request->user()->id
                ]);

                // Simpan rute-rute yang dipilih
                foreach ($request->routes as $route) {
                    $routeData = \App\Models\Routes::findOrFail($route['route_id']);
                    \App\Models\ListGroupRoute::create([
                        'route_group_id' => $routeGroup->id,
                        'route_id' => $route['route_id'],
                        'time_difference' => $route['time_difference'],
                        'start_location_id' => $routeData->start_location_id,
                        'end_location_id' => $routeData->end_location_id,
                        'created_by_id' => $request->user()->id,
                        'updated_by_id' => $request->user()->id
                    ]);
                }

                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'Grup rute berhasil dibuat',
                    'data' => $routeGroup->load(['routes.route'])
                ], 201);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
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
                'description' => 'nullable|string',
                'is_active' => 'boolean',
                'routes' => 'required|array|min:1',
                'routes.*.route_id' => 'required|exists:routes,id',
                'routes.*.time_difference' => 'required|integer|min:15'
            ], [
                'routes.required' => 'Rute harus diisi',
                'routes.min' => 'Minimal harus memilih 1 rute',
                'routes.*.route_id.required' => 'ID Rute harus diisi',
                'routes.*.route_id.exists' => 'Rute tidak valid',
                'routes.*.time_difference.required' => 'Selisih waktu harus diisi',
                'routes.*.time_difference.min' => 'Selisih waktu minimal 15 menit'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();
            try {
                // Ambil lokasi awal dan akhir dari rute pertama
                $firstRoute = \App\Models\Routes::findOrFail($request->routes[0]['route_id']);

                $routeGroup->update([
                    'name' => $request->name,
                    'description' => $request->description,
                    'is_active' => $request->is_active ?? $routeGroup->is_active,
                    'updated_by_id' => $request->user()->id
                ]);

                // Hapus semua rute yang ada sebelumnya
                \App\Models\ListGroupRoute::where('route_group_id', $routeGroup->id)->delete();

                // Simpan rute-rute yang baru
                foreach ($request->routes as $route) {
                    $routeData = \App\Models\Routes::findOrFail($route['route_id']);
                    \App\Models\ListGroupRoute::create([
                        'route_group_id' => $routeGroup->id,
                        'route_id' => $route['route_id'],
                        'time_difference' => $route['time_difference'],
                        'start_location_id' => $routeData->start_location_id,
                        'end_location_id' => $routeData->end_location_id,
                        'created_by_id' => $request->user()->id,
                        'updated_by_id' => $request->user()->id
                    ]);
                }

                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'Grup rute berhasil diperbarui',
                    'data' => $routeGroup->load(['routes.route'])
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }
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

    public function getAllRouteGroups()
    {
        try {
            $routeGroups = RouteGroup::select('id', 'name')
                ->where('is_active', true)
                ->get();

            return response()->json([
                'status' => true,
                'data' => $routeGroups
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data grup rute',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getRouteGroupDetail($id)
    {
        try {
            $routeGroup = RouteGroup::with(['routes' => function($query) {
                $query->select('list_group_route.id', 'route_id', 'route_group_id', 'time_difference')
                    ->with(['route' => function($q) {
                        $q->select('id', 'start_location_id', 'end_location_id', 'distance', 'price')
                            ->with(['startLocation:id,name', 'endLocation:id,name']);
                    }]);
            }])->findOrFail($id);

            return response()->json([
                'status' => true,
                'data' => [
                    'id' => $routeGroup->id,
                    'name' => $routeGroup->name,
                    'description' => $routeGroup->description,
                    'is_active' => $routeGroup->is_active,
                    'routes' => $routeGroup->routes->map(function($route) {
                        return [
                            'id' => $route->id,
                            'time_difference' => $route->time_difference,
                            'route' => [
                                'id' => $route->route->id,
                                'start_location' => $route->route->startLocation->name,
                                'end_location' => $route->route->endLocation->name,
                                'distance' => $route->route->distance,
                                'price' => $route->route->price
                            ]
                        ];
                    })
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Grup rute tidak ditemukan',
                'error' => $e->getMessage()
            ], $e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException ? 404 : 500);
        }
    }
} 
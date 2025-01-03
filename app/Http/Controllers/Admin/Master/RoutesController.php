<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Routes\StoreRouteRequest;
use App\Http\Requests\Routes\UpdateRouteRequest;
use App\Models\Routes;
use Illuminate\Http\Request;

class RoutesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['start_location', 'end_location', 'distance', 'price']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $routes = Routes::filterWithJoin($filters)->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'data' => $routes->items(),
                'current_page' => $routes->currentPage(),
                'total_pages' => $routes->lastPage(),
                'total_items' => $routes->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch routes', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $route = Routes::findOrFail($id);

            return response()->json($route, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch route', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreRouteRequest $request)
    {
        try {
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

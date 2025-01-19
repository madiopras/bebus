<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\Locations\StoreLocationRequest;
use App\Http\Requests\Locations\UpdateLocationRequest;
use App\Models\Locations;
use Illuminate\Http\Request;

class LocationsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['name', 'state', 'place', 'address', 'created_by_id', 'updated_by_id']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $locations = Locations::filter($filters)->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'data' => $locations->items(),
                'current_page' => $locations->currentPage(),
                'total_pages' => $locations->lastPage(),
                'total_items' => $locations->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch locations', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $location = Locations::findOrFail($id);

            return response()->json($location, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch location', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreLocationRequest $request)
    {
        try {
            $location = Locations::create([
                'name' => $request->name,
                'state' => $request->state,
                'place' => $request->place,
                'address' => $request->address,
                'created_by_id' => $request->user()->id,
                'updated_by_id' => $request->user()->id,
            ]);

            return response()->json(['message' => 'Location created successfully', 'location' => $location], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create location', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateLocationRequest $request, $id)
    {
        $location = Locations::find($id);

        if (!$location) {
            return response()->json(['message' => 'Location not found'], 404);
        }

        try {
            $location->update($request->only(['name', 'state', 'place', 'address']));

            $location->updated_by_id = $request->user()->id;
            $location->save();

            return response()->json(['message' => 'Location updated successfully', 'location' => $location], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update location', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $location = Locations::find($id);

            if (!$location) {
                return response()->json(['message' => 'Location not found'], 404);
            }

            $location->delete();

            return response()->json(['message' => 'Location deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete location', 'error' => $e->getMessage()], 500);
        }
    }

    public function getLocations()
    {
        try {
            $states = Locations::select('state')
                ->distinct()
                ->whereNotNull('state')
                ->orderBy('state')
                ->pluck('state');

            return response()->json([
                'status' => true,
                'data' => $states
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data lokasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getNameLocations()
    {
        try {
            $locations = Locations::select('id', 'name', 'place')
                ->orderBy('name')
                ->get();

            return response()->json([
                'status' => true,
                'data' => $locations
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data nama lokasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\Request;
use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;

class RolesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['name', 'guard_name']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $roles = Role::with('description')
                ->filter($filters)
                ->paginate($limit, ['*'], 'page', $page);

            $formattedRoles = collect($roles->items())->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'description' => $role->description ? $role->description->description : null
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $formattedRoles,
                'current_page' => $roles->currentPage(),
                'total_pages' => $roles->lastPage(),
                'total_items' => $roles->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch roles', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $role = Role::with('description')->findOrFail($id);

            return response()->json([
                'name' => $role->name,
                'guard_name' => $role->guard_name,
                'description' => $role->description ? $role->description->description : null
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch role', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreRoleRequest $request)
    {
        try {
            $role = Role::create([
                'name' => $request->name,
                'guard_name' => $request->guard_name ?? 'web',
            ]);

            if ($request->has('description')) {
                $role->description()->create([
                    'description' => $request->description
                ]);
            }

            return response()->json([
                'message' => 'Role created successfully', 
                'role' => [
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'description' => $role->description ? $role->description->description : null
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create role', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateRoleRequest $request, $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return response()->json(['message' => 'Role not found'], 404);
        }

        try {
            $role->update($request->only(['name', 'guard_name']));
            
            if ($request->has('description')) {
                $role->description()->updateOrCreate(
                    ['role_id' => $role->id],
                    ['description' => $request->description]
                );
            }
            
            return response()->json([
                'message' => 'Role updated successfully', 
                'role' => [
                    'name' => $role->name,
                    'guard_name' => $role->guard_name,
                    'description' => $role->description ? $role->description->description : null
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update role', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $role = Role::find($id);

            if (!$role) {
                return response()->json(['message' => 'Role not found'], 404);
            }

            $role->delete();

            return response()->json(['message' => 'Role deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete role', 'error' => $e->getMessage()], 500);
        }
    }
} 
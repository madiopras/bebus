<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Menu;
use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleManagementController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['name']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $roles = Role::select('roles.*')
                ->join('role_has_permissions', 'roles.id', '=', 'role_has_permissions.role_id')
                ->leftJoin('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->with(['permissions'])
                ->distinct('roles.id')
                ->filter($filters)
                ->paginate($limit, ['roles.*'], 'page', $page);

            $menus = Menu::all();
            
            $formattedRoles = collect($roles->items())->map(function ($role) use ($menus) {
                $menuPermissions = [];
                
                if ($role->permissions->isNotEmpty()) {
                    $permissions = $role->permissions->pluck('name')->toArray();
                    
                    foreach ($menus as $menu) {
                        $menuName = $menu->menu_name;
                        $hasPermission = collect($permissions)->filter(function($permission) use ($menuName) {
                            return strpos($permission, 'create_' . $menuName) !== false ||
                                   strpos($permission, 'read_' . $menuName) !== false ||
                                   strpos($permission, 'update_' . $menuName) !== false ||
                                   strpos($permission, 'delete_' . $menuName) !== false;
                        })->isNotEmpty();

                        if ($hasPermission) {
                            $menuPermissions[] = $menuName;
                        }
                    }
                }

                return [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'menu_permissions' => $menuPermissions
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $formattedRoles,
                'menus' => $menus,
                'current_page' => $roles->currentPage(),
                'total_pages' => $roles->lastPage(),
                'total_items' => $roles->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data role management', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($roleId)
    {
        try {
            $role = Role::select('roles.*')
                ->join('role_has_permissions', 'roles.id', '=', 'role_has_permissions.role_id')
                ->leftJoin('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
                ->with(['permissions'])
                ->where('roles.id', $roleId)
                ->distinct()
                ->firstOrFail();
            
            return response()->json([
                'role_id' => (string) $role->id,
                'permissions' => $role->permissions->pluck('name')->toArray()
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data role management', 'error' => $e->getMessage()], 500);
        }
    }

    public function updatePermissions(Request $request, $roleId)
    {
        $request->validate([
            'menu_permissions' => 'required|array',
            'menu_permissions.*.menu_id' => 'required|exists:menus,id',
            'menu_permissions.*.permissions' => 'required|array',
            'menu_permissions.*.permissions.create' => 'required|boolean',
            'menu_permissions.*.permissions.update' => 'required|boolean',
            'menu_permissions.*.permissions.delete' => 'required|boolean',
        ]);

        try {
            DB::beginTransaction();

            $role = Role::findOrFail($roleId);
            $permissionsToSync = [];

            foreach ($request->menu_permissions as $menuPermission) {
                $menu = Menu::findOrFail($menuPermission['menu_id']);
                $permissions = $menuPermission['permissions'];

                $permissionTypes = [
                    'create' => 'create_' . $menu->menu_name,
                    'update' => 'update_' . $menu->menu_name,
                    'delete' => 'delete_' . $menu->menu_name,
                ];

                foreach ($permissionTypes as $type => $permissionName) {
                    if ($permissions[$type]) {
                        // Cari atau buat permission jika belum ada
                        $permission = Permission::firstOrCreate(['name' => $permissionName]);
                        $permissionsToSync[] = $permission->id;
                    }
                }
            }

            // Sync permissions ke role
            $role->permissions()->sync($permissionsToSync);

            DB::commit();

            return response()->json([
                'message' => 'Permissions berhasil diperbarui',
                'role_id' => $role->id,
                'role_name' => $role->name
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal memperbarui permissions', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'required|array',
            'permissions.*' => 'string'
        ]);

        try {
            DB::beginTransaction();

            $role = Role::findOrFail($request->role_id);
            $permissionsToSync = [];

            foreach ($request->permissions as $permissionName) {
                // Cari atau buat permission baru jika tidak ditemukan
                $permission = Permission::firstOrCreate(['name' => $permissionName]);
                $permissionsToSync[] = $permission->id;
            }

            // Sync permissions ke role
            $role->permissions()->sync($permissionsToSync);

            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Role permissions updated successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $roleId)
    {
        $request->validate([
            'role_id' => 'required|string',
            'permissions' => 'required|array',
            'permissions.*' => 'string'
        ]);

        try {
            DB::beginTransaction();

            $role = Role::findOrFail($roleId);
            $permissionsToSync = [];

            foreach ($request->permissions as $permissionName) {
                $permission = Permission::firstOrCreate(['name' => $permissionName]);
                $permissionsToSync[] = $permission->id;
            }

            $role->permissions()->sync($permissionsToSync);

            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Role permissions berhasil diperbarui',
                'data' => [
                    'role_id' => (string) $role->id,
                    'permissions' => $role->permissions->pluck('name')->toArray()
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($roleId)
    {
        try {
            DB::beginTransaction();
            
            $role = Role::findOrFail($roleId);
            // Hapus semua permissions yang terkait dengan role
            $role->permissions()->detach();
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Role permissions berhasil dihapus'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
} 
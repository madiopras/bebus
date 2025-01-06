<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;

class UserRoleController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['name', 'role']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $users = User::with('roles')
                ->filter($filters)
                ->paginate($limit, ['*'], 'page', $page);

            $formattedUsers = collect($users->items())->map(function ($user) {
                $role = $user->roles->first();
                return [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'role_id' => $role ? $role->id : null,
                    'role_name' => $role ? $role->name : null
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $formattedUsers,
                'current_page' => $users->currentPage(),
                'total_pages' => $users->lastPage(),
                'total_items' => $users->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data user roles', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($userId)
    {
        try {
            $user = User::with('roles')->findOrFail($userId);
            $role = $user->roles->first();

            return response()->json([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'role_id' => $role ? $role->id : null,
                'role_name' => $role ? $role->name : null
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data user role', 'error' => $e->getMessage()], 500);
        }
    }

    public function assign(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_id' => 'required|exists:roles,id'
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            $role = Role::findOrFail($request->role_id);

            // Hapus semua role yang ada sebelum menambahkan yang baru
            $user->roles()->detach();
            $user->assignRole($role);

            return response()->json([
                'message' => 'Role berhasil ditambahkan ke user',
                'user_id' => $user->id,
                'user_name' => $user->name,
                'role_id' => $role->id,
                'role_name' => $role->name
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menambahkan role ke user', 'error' => $e->getMessage()], 500);
        }
    }

    public function revoke(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        try {
            $user = User::findOrFail($request->user_id);
            
            // Hapus semua role dari user
            $user->roles()->detach();

            return response()->json([
                'message' => 'Role berhasil dihapus dari user',
                'user_id' => $user->id,
                'user_name' => $user->name,
                'role_id' => null,
                'role_name' => null
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus role dari user', 'error' => $e->getMessage()], 500);
        }
    }
} 
<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Models\ModelHasRole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignRoleController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['role_id', 'model_type', 'user_id']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $query = ModelHasRole::with(['role'])
                ->when(isset($filters['role_id']), function ($q) use ($filters) {
                    return $q->where('role_id', $filters['role_id']);
                })
                ->when(isset($filters['model_type']), function ($q) use ($filters) {
                    return $q->where('model_type', $filters['model_type']);
                })
                ->when(isset($filters['user_id']), function ($q) use ($filters) {
                    return $q->where('model_id', $filters['user_id']);
                });

            $assignments = $query->get();

            $groupedAssignments = $assignments->groupBy('role_id')->map(function ($group) {
                $firstItem = $group->first();
                return [
                    'role_id' => $firstItem->role->id,
                    'role_name' => $firstItem->role->name,
                    'model_type' => $firstItem->model_type,
                    'users' => $group->map(function ($assignment) {
                        $user = User::find($assignment->model_id);
                        if (!$user) return null;
                        return [
                            'id' => $user->id,
                            'name' => $user->name
                        ];
                    })->filter()->values(),
                    'user_id' => $group->map(function ($assignment) {
                        $user = User::find($assignment->model_id);
                        return $user ? $user->id : null;
                    })->filter()->implode(', '),
                    'user_name' => $group->map(function ($assignment) {
                        $user = User::find($assignment->model_id);
                        return $user ? $user->name : null;
                    })->filter()->implode(', ')
                ];
            })->values();

            // Menerapkan paginasi manual
            $total = $groupedAssignments->count();
            $perPage = $limit;
            $currentPage = $page;
            $offset = ($currentPage - 1) * $perPage;
            
            $paginatedAssignments = $groupedAssignments->slice($offset, $perPage);

            return response()->json([
                'status' => true,
                'data' => $paginatedAssignments,
                'current_page' => $currentPage,
                'total_pages' => ceil($total / $perPage),
                'total_items' => $total
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data assign role', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $assignments = ModelHasRole::with(['role'])
                ->where('role_id', $id)
                ->get();

            if ($assignments->isEmpty()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Data tidak ditemukan'
                ], 404);
            }

            $firstItem = $assignments->first();
            $formattedData = [
                'role_id' => $firstItem->role->id,
                'role_name' => $firstItem->role->name,
                'model_type' => $firstItem->model_type,
                'users' => $assignments->map(function ($assignment) {
                    $user = User::find($assignment->model_id);
                    if (!$user) return null;
                    return [
                        'id' => $user->id,
                        'name' => $user->name
                    ];
                })->filter()->values(),
                'user_id' => $assignments->map(function ($assignment) {
                    $user = User::find($assignment->model_id);
                    return $user ? $user->id : null;
                })->filter()->implode(', '),
                'user_name' => $assignments->map(function ($assignment) {
                    $user = User::find($assignment->model_id);
                    return $user ? $user->name : null;
                })->filter()->implode(', ')
            ];

            return response()->json([
                'status' => true,
                'data' => $formattedData
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data assign role', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'model_type' => 'required|string',
            'user_id' => 'required|array',
            'user_id.*' => 'exists:users,id'
        ]);

        try {
            DB::beginTransaction();

            $results = [];
            foreach ($request->user_id as $userId) {
                // Cek apakah assignment sudah ada
                $existingAssignment = ModelHasRole::where([
                    'role_id' => $request->role_id,
                    'model_type' => $request->model_type,
                    'model_id' => $userId
                ])->first();

                if (!$existingAssignment) {
                    $assignment = ModelHasRole::create([
                        'role_id' => $request->role_id,
                        'model_type' => $request->model_type,
                        'model_id' => $userId
                    ]);

                    $user = User::find($userId);
                    $role = Role::find($request->role_id);

                    $results[] = [
                        'user_id' => $user->id,
                        'user_name' => $user->name,
                        'role_id' => $role->id,
                        'role_name' => $role->name,
                        'model_type' => $assignment->model_type
                    ];
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Role berhasil di-assign',
                'data' => $results
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal assign role', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'role_id' => 'required|exists:roles,id',
            'model_type' => 'required|string',
            'user_id' => 'required|array',
            'user_id.*' => 'exists:users,id'
        ]);

        try {
            DB::beginTransaction();

            // Hapus assignment lama untuk role_id yang diberikan
            ModelHasRole::where('role_id', $id)->delete();

            $results = [];
            foreach ($request->user_id as $userId) {
                $assignment = ModelHasRole::create([
                    'role_id' => $request->role_id,
                    'model_type' => $request->model_type,
                    'model_id' => $userId
                ]);

                $user = User::find($userId);
                $role = Role::find($request->role_id);

                $results[] = [
                    'user_id' => $user->id,
                    'user_name' => $user->name,
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'model_type' => $assignment->model_type
                ];
            }

            DB::commit();

            return response()->json([
                'message' => 'Assignment berhasil diupdate',
                'data' => $results
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal update assignment', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            DB::beginTransaction();

            ModelHasRole::where('role_id', $id)->delete();

            DB::commit();

            return response()->json(['message' => 'Assignment berhasil dihapus'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Gagal menghapus assignment', 'error' => $e->getMessage()], 500);
        }
    }
} 
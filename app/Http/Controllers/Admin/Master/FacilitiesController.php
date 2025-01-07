<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\Facility;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FacilitiesController extends Controller
{
    public function index(Request $request)
    {
        try {
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $facilities = Facility::with(['createdBy', 'updatedBy'])
                ->paginate($limit, ['*'], 'page', $page);

            $formattedFacilities = collect($facilities->items())->map(function ($facility) {
                return [
                    'id' => $facility->id,
                    'name' => $facility->name,
                    'description' => $facility->description,
                    'created_by' => $facility->createdBy ? $facility->createdBy->name : null,
                    'updated_by' => $facility->updatedBy ? $facility->updatedBy->name : null
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $formattedFacilities,
                'current_page' => $facilities->currentPage(),
                'total_pages' => $facilities->lastPage(),
                'total_items' => $facilities->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data fasilitas', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $facility = Facility::with(['createdBy', 'updatedBy'])->findOrFail($id);

            return response()->json([
                'id' => $facility->id,
                'name' => $facility->name,
                'description' => $facility->description,
                'created_by' => $facility->createdBy ? $facility->createdBy->name : null,
                'updated_by' => $facility->updatedBy ? $facility->updatedBy->name : null
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal mengambil data fasilitas', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string'
            ]);

            $facility = Facility::create([
                'name' => $request->name,
                'description' => $request->description,
                'created_by_id' => Auth::id(),
                'updated_by_id' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Fasilitas berhasil dibuat', 
                'facility' => [
                    'id' => $facility->id,
                    'name' => $facility->name,
                    'description' => $facility->description,
                    'created_by' => Auth::user()->name,
                    'updated_by' => Auth::user()->name
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal membuat fasilitas', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string'
            ]);

            $facility = Facility::findOrFail($id);

            $facility->update([
                'name' => $request->name,
                'description' => $request->description,
                'updated_by_id' => Auth::id()
            ]);

            return response()->json([
                'message' => 'Fasilitas berhasil diperbarui', 
                'facility' => [
                    'id' => $facility->id,
                    'name' => $facility->name,
                    'description' => $facility->description,
                    'created_by' => $facility->createdBy ? $facility->createdBy->name : null,
                    'updated_by' => Auth::user()->name
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal memperbarui fasilitas', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $facility = Facility::findOrFail($id);
            $facility->delete();

            return response()->json(['message' => 'Fasilitas berhasil dihapus'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Gagal menghapus fasilitas', 'error' => $e->getMessage()], 500);
        }
    }
} 
<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Models\Menu;
use Illuminate\Http\Request;
use App\Http\Requests\Menu\StoreMenuRequest;
use App\Http\Requests\Menu\UpdateMenuRequest;

class MenusController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['menu_name', 'description', 'model_type']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $menus = Menu::filter($filters)
                ->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'data' => $menus->items(),
                'current_page' => $menus->currentPage(),
                'total_pages' => $menus->lastPage(),
                'total_items' => $menus->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch menus', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $menu = Menu::findOrFail($id);

            return response()->json($menu, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch menu', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreMenuRequest $request)
    {
        try {
            $menu = Menu::create($request->validated());

            return response()->json([
                'message' => 'Menu created successfully', 
                'menu' => $menu
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create menu', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateMenuRequest $request, $id)
    {
        try {
            $menu = Menu::findOrFail($id);
            $menu->update($request->validated());
            
            return response()->json([
                'message' => 'Menu updated successfully', 
                'menu' => $menu
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update menu', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $menu = Menu::findOrFail($id);
            $menu->delete();

            return response()->json(['message' => 'Menu deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete menu', 'error' => $e->getMessage()], 500);
        }
    }

    public function getAllMenus()
    {
        try {
            $menus = Menu::select('id', 'menu_name', 'model_type')->get();

            return response()->json([
                'status' => true,
                'data' => $menus->isEmpty() ? [] : $menus
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data menu',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

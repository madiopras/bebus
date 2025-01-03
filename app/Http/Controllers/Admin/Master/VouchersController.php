<?php

namespace App\Http\Controllers\Admin\Master;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreVoucherRequest;
use App\Http\Requests\UpdateVoucherRequest;
use App\Models\Voucher;
use Illuminate\Http\Request;

class VouchersController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['code', 'description', 'discount_percentage', 'start_date', 'end_date', 'is_active', 'created_by_id', 'updated_by_id']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $vouchers = Voucher::filter($filters)->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'data' => $vouchers->items(),
                'current_page' => $vouchers->currentPage(),
                'total_pages' => $vouchers->lastPage(),
                'total_items' => $vouchers->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch vouchers', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $voucher = Voucher::findOrFail($id);

            return response()->json($voucher, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch voucher', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreVoucherRequest $request)
    {
        try {
            $voucher = Voucher::create([
                'code' => $request->code,
                'description' => $request->description,
                'discount_percentage' => $request->discount_percentage,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'is_active' => $request->is_active,
                'created_by_id' => $request->user()->id,
                'updated_by_id' => $request->user()->id,
            ]);

            return response()->json(['message' => 'Voucher created successfully', 'voucher' => $voucher], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create voucher', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdateVoucherRequest $request, $id)
    {
        $voucher = Voucher::find($id);

        if (!$voucher) {
            return response()->json(['message' => 'Voucher not found'], 404);
        }

        try {
            $voucher->update($request->only(['code', 'description', 'discount_percentage', 'start_date', 'end_date', 'is_active']));

            $voucher->updated_by_id = $request->user()->id;
            $voucher->save();

            return response()->json(['message' => 'Voucher updated successfully', 'voucher' => $voucher], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update voucher', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $voucher = Voucher::find($id);

            if (!$voucher) {
                return response()->json(['message' => 'Voucher not found'], 404);
            }

            $voucher->delete();

            return response()->json(['message' => 'Voucher deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete voucher', 'error' => $e->getMessage()], 500);
        }
    }
}

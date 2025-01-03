<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentsController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['booking_id', 'payment_method', 'payment_date', 'amount', 'description', 'created_by_id', 'updated_by_id']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $payments = Payment::filter($filters)->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'data' => $payments->items(),
                'current_page' => $payments->currentPage(),
                'total_pages' => $payments->lastPage(),
                'total_items' => $payments->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch payments', 'error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $payment = Payment::findOrFail($id);

            return response()->json($payment, 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch payment', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StorePaymentRequest $request)
    {
        try {
            $payment = Payment::create([
                'booking_id' => $request->booking_id,
                'payment_method' => $request->payment_method,
                'payment_date' => $request->payment_date,
                'amount' => $request->amount,
                'description' => $request->description,
                'created_by_id' => $request->user()->id,
                'updated_by_id' => $request->user()->id,
            ]);

            return response()->json(['message' => 'Payment created successfully', 'payment' => $payment], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create payment', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(UpdatePaymentRequest $request, $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json(['message' => 'Payment not found'], 404);
        }

        try {
            $payment->update($request->only(['booking_id', 'payment_method', 'payment_date', 'amount', 'description']));

            $payment->updated_by_id = $request->user()->id;
            $payment->save();

            return response()->json(['message' => 'Payment updated successfully', 'payment' => $payment], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update payment', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $payment = Payment::find($id);

            if (!$payment) {
                return response()->json(['message' => 'Payment not found'], 404);
            }

            $payment->delete();

            return response()->json(['message' => 'Payment deleted successfully'], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete payment', 'error' => $e->getMessage()], 500);
        }
    }
}

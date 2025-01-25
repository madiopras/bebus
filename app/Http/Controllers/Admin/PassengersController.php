<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePassengerRequest;
use App\Http\Requests\UpdatePassengerRequest;
use App\Models\Passenger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PassengersController extends Controller
{
    public function index(Request $request)
    {
        try {
            $filters = $request->only(['booking_id', 'schedule_seat_id', 'name', 'phone_number', 'description', 'created_by_id', 'updated_by_id']);
            $limit = $request->query('limit', 10);
            $page = $request->query('page', 1);

            $passengers = Passenger::filter($filters)
                ->with(['booking', 'scheduleSeat'])
                ->paginate($limit, ['*'], 'page', $page);

            return response()->json([
                'status' => true,
                'data' => $passengers->items(),
                'current_page' => $passengers->currentPage(),
                'total_pages' => $passengers->lastPage(),
                'total_items' => $passengers->total()
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch passengers', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $passenger = Passenger::with(['booking', 'scheduleSeat'])
                ->findOrFail($id);

            return response()->json([
                'status' => true,
                'data' => $passenger
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to fetch passenger', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function store(StorePassengerRequest $request)
    {
        DB::beginTransaction();
        try {
            // Validasi schedule_seat tersedia
            $scheduleSeat = DB::table('schedule_seats')
                ->where('id', $request->schedule_seat_id)
                ->where('is_available', true)
                ->first();

            if (!$scheduleSeat) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kursi tidak tersedia'
                ], 422);
            }

            $passenger = Passenger::create([
                'booking_id' => $request->booking_id,
                'schedule_seat_id' => $request->schedule_seat_id,
                'name' => $request->name,
                'phone_number' => $request->phone_number,
                'description' => $request->description,
                'created_by_id' => $request->user()->id,
                'updated_by_id' => $request->user()->id,
            ]);

            // Update status kursi
            DB::table('schedule_seats')
                ->where('id', $request->schedule_seat_id)
                ->update(['is_available' => false]);

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Passenger created successfully', 
                'data' => $passenger
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to create passenger', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(UpdatePassengerRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $passenger = Passenger::findOrFail($id);

            // Jika schedule_seat berubah
            if ($request->filled('schedule_seat_id') && $request->schedule_seat_id != $passenger->schedule_seat_id) {
                // Validasi kursi baru tersedia
                $newScheduleSeat = DB::table('schedule_seats')
                    ->where('id', $request->schedule_seat_id)
                    ->where('is_available', true)
                    ->first();

                if (!$newScheduleSeat) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Kursi baru tidak tersedia'
                    ], 422);
                }

                // Update status kursi lama menjadi tersedia
                DB::table('schedule_seats')
                    ->where('id', $passenger->schedule_seat_id)
                    ->update(['is_available' => true]);

                // Update status kursi baru menjadi tidak tersedia  
                DB::table('schedule_seats')
                    ->where('id', $request->schedule_seat_id)
                    ->update(['is_available' => false]);
            }

            $passenger->update($request->only([
                'booking_id', 
                'schedule_seat_id', 
                'name', 
                'phone_number', 
                'description'
            ]));

            $passenger->updated_by_id = $request->user()->id;
            $passenger->save();

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Passenger updated successfully', 
                'data' => $passenger
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to update passenger', 
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $passenger = Passenger::findOrFail($id);

            // Update status kursi menjadi tersedia
            DB::table('schedule_seats')
                ->where('id', $passenger->schedule_seat_id)
                ->update(['is_available' => true]);

            $passenger->delete();

            DB::commit();
            return response()->json([
                'status' => true,
                'message' => 'Passenger deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'message' => 'Failed to delete passenger', 
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

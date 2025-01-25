<?php

namespace App\Http\Controllers\Admin\Master;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Requests\Buses\StoreBusRequest;
use App\Http\Requests\Buses\UpdateBusRequest;
use App\Models\Buses;
use App\Models\Seats;
use App\Models\Classes;
use Illuminate\Http\Request;

class BusesController extends Controller
{
    
    public function index(Request $request)
{
    try {
        $filters = $request->only(['bus_number', 'type_bus', 'class_name', 'bus_name', 'is_active']);
        $limit = $request->query('limit', 10);
        $page = $request->query('page', 1);
        
        // Tangkap parameter dateTime dari request
        $dateTime = $request->only(['dateTime']); // Default value jika tidak ada

        // Mengambil data bus dengan filter dan mengecualikan yang ada dalam jadwal
        $buses = Buses::filterWithJoin($filters)
                      ->filterBusesNotInSchedule($dateTime)
                      ->paginate($limit, ['*'], 'page', $page);

        // Ambil semua kelas yang tersedia
        $classes = Classes::select('id', 'class_name')->get();

        return response()->json([
            'status' => true,
            'data' => $buses->items(),
            'classes' => $classes,
            'current_page' => $buses->currentPage(),
            'total_pages' => $buses->lastPage(),
            'total_items' => $buses->total()
        ], 200);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Failed to fetch buses', 'error' => $e->getMessage()], 500);
    }
}

    public function show($id)
    {
        try {
            $bus = Buses::findOrFail($id);
            
            // Ambil semua kelas yang tersedia
            $classes = Classes::select('id', 'class_name')->get();

            // Ambil nomor kursi yang tidak aktif
            $notUsedSeats = Seats::where('bus_id', $id)
                                ->where('is_active', false)
                                ->pluck('seat_number')
                                ->implode(',');

            return response()->json([
                'bus' => $bus,
                'classes' => $classes,
                'not_used' => $notUsedSeats
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch bus', 'error' => $e->getMessage()], 500);
        }
    }

    public function store(StoreBusRequest $request)
{
    DB::beginTransaction();

    try {
        $bus = Buses::create([
            'bus_number' => $request->bus_number,
            'type_bus' => $request->type_bus,
            'capacity' => $request->capacity,
            'bus_name' => $request->bus_name,
            'class_id' => $request->class_id,
            'description' => $request->description,
            'cargo' => $request->cargo,
            'is_active' => $request->is_active,
            'created_by_id' => $request->user()->id,
            'updated_by_id' => $request->user()->id,
        ]);

        // Convert not_used string to array
        $notUsedSeats = $request->not_used ? explode(',', $request->not_used) : [];

        for ($i = 1; $i <= $request->capacity; $i++) {
            Seats::create([
                'bus_id' => $bus->id,
                'seat_number' => $i,
                'is_active' => !in_array($i, $notUsedSeats),
                'created_by_id' => $request->user()->id,
                'updated_by_id' => $request->user()->id,
            ]);
        }

        DB::commit();

        return response()->json(['message' => 'Bus created successfully', 'bus' => $bus], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Failed to create bus', 'error' => $e->getMessage()], 500);
    }
}


public function update(UpdateBusRequest $request, $id)
{
    DB::beginTransaction();

    try {
        $bus = Buses::findOrFail($id);
        $bus->update([
            'bus_number' => $request->bus_number,
            'type_bus' => $request->type_bus,
            'capacity' => $request->capacity,
            'bus_name' => $request->bus_name,
            'class_id' => $request->class_id,
            'description' => $request->description,
            'cargo' => $request->cargo,
            'is_active' => $request->is_active,
            'updated_by_id' => $request->user()->id,
        ]);

        // Convert not_used string to array
        $notUsedSeats = $request->not_used ? explode(',', $request->not_used) : [];

        // Update kursi yang sudah ada
        $existingSeats = Seats::where('bus_id', $bus->id)->get();
        foreach ($existingSeats as $seat) {
            $seat->update([
                'is_active' => !in_array($seat->seat_number, $notUsedSeats),
                'updated_by_id' => $request->user()->id
            ]);
        }

        // Jika kapasitas bertambah, tambahkan kursi baru
        $currentSeatCount = $existingSeats->count();
        if ($request->capacity > $currentSeatCount) {
            for ($i = $currentSeatCount + 1; $i <= $request->capacity; $i++) {
                Seats::create([
                    'bus_id' => $bus->id,
                    'seat_number' => $i,
                    'is_active' => !in_array($i, $notUsedSeats),
                    'created_by_id' => $request->user()->id,
                    'updated_by_id' => $request->user()->id,
                ]);
            }
        }
        // Jika kapasitas berkurang, hapus kursi yang tidak digunakan
        else if ($request->capacity < $currentSeatCount) {
            Seats::where('bus_id', $bus->id)
                 ->where('seat_number', '>', $request->capacity)
                 ->delete();
        }

        DB::commit();

        return response()->json(['message' => 'Bus updated successfully', 'bus' => $bus], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Failed to update bus', 'error' => $e->getMessage()], 500);
    }
}

public function destroy($id)
{
    DB::beginTransaction();

    try {
        $bus = Buses::findOrFail($id);

        // Hapus kursi yang terkait dengan bus
        Seats::where('bus_id', $bus->id)->delete();

        // Hapus bus
        $bus->delete();

        DB::commit();

        return response()->json(['message' => 'Bus deleted successfully'], 200);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Failed to delete bus', 'error' => $e->getMessage()], 500);
    }
}
}

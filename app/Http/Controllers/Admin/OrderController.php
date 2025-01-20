<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bookings;
use App\Models\Passenger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Bookings::select(
                'bookings.*',
                'sr.departure_time',
                'sr.arrival_time',
                'sr.price_rute',
                'l.name as dari',
                'l2.name as ke',
                'b.bus_number as kode_bus',
                'b.bus_name as nama_bus',
                'b.type_bus as tipe_bus',
                'c.class_name as kelas_bus',
                'users.name as created_by_name'
            )
            ->leftJoin('schedule_rute as sr', 'bookings.schedule_id', '=', 'sr.id')
            ->leftJoin('routes as r', 'sr.route_id', '=', 'r.id')
            ->leftJoin('locations as l', 'r.start_location_id', '=', 'l.id')
            ->leftJoin('locations as l2', 'r.end_location_id', '=', 'l2.id')
            ->leftJoin('schedules as s', 'sr.schedule_id', '=', 's.id')
            ->leftJoin('buses as b', 's.bus_id', '=', 'b.id')
            ->leftJoin('classes as c', 'b.class_id', '=', 'c.id')
            ->leftJoin('users', 'bookings.created_by_id', '=', 'users.id')
            ->with(['passengers' => function($query) {
                $query->select('passengers.*', 'seats.seat_number')
                    ->leftJoin('seats', 'passengers.schedule_seat_id', '=', 'seats.id');
            }]);

            // Filter berdasarkan status pembayaran
            if ($request->has('status')) {
                $query->where('bookings.payment_status', $request->status);
            }

            // Filter berdasarkan departure_time
            if ($request->has('departure_time')) {
                $now = Carbon::now();
                if ($request->departure_time === 'after') {
                    $query->where('sr.departure_time', '>', $now);
                } elseif ($request->departure_time === 'before') {
                    $query->where('sr.departure_time', '<', $now);
                }
            }

            // Filter berdasarkan tanggal booking
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('bookings.booking_date', [
                    Carbon::parse($request->start_date)->startOfDay(),
                    Carbon::parse($request->end_date)->endOfDay()
                ]);
            }

            // Filter berdasarkan waktu keberangkatan
            if ($request->has('departure_start') && $request->has('departure_end')) {
                $query->whereBetween('sr.departure_time', [
                    Carbon::parse($request->departure_start),
                    Carbon::parse($request->departure_end)
                ]);
            }

            // Filter berdasarkan waktu kedatangan
            if ($request->has('arrival_start') && $request->has('arrival_end')) {
                $query->whereBetween('sr.arrival_time', [
                    Carbon::parse($request->arrival_start),
                    Carbon::parse($request->arrival_end)
                ]);
            }

            // Filter berdasarkan customer type
            if ($request->has('customer_type')) {
                $query->where('bookings.customer_type', $request->customer_type);
            }

            // Filter berdasarkan payment_id
            if ($request->has('payment_id')) {
                $query->where('bookings.payment_id', 'like', '%' . $request->payment_id . '%');
            }

            // Filter berdasarkan pencarian nama/email/phone
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('bookings.name', 'like', "%{$search}%")
                      ->orWhere('bookings.email', 'like', "%{$search}%")
                      ->orWhere('bookings.phone_number', 'like', "%{$search}%");
                });
            }

            // Hitung statistik
            $statistics = [
                'total_bookings' => DB::table('bookings')->count(),
                'total_revenue' => DB::table('bookings')
                    ->where('payment_status', 'PAID')
                    ->when($request->has('start_date') && $request->has('end_date'), function($query) use ($request) {
                        return $query->whereBetween('booking_date', [
                            Carbon::parse($request->start_date)->startOfDay(),
                            Carbon::parse($request->end_date)->endOfDay()
                        ]);
                    })
                    ->sum('final_price'),
                'payment_status_count' => DB::table('bookings')
                    ->select('payment_status', DB::raw('count(*) as total'))
                    ->when($request->has('start_date') && $request->has('end_date'), function($query) use ($request) {
                        return $query->whereBetween('booking_date', [
                            Carbon::parse($request->start_date)->startOfDay(),
                            Carbon::parse($request->end_date)->endOfDay()
                        ]);
                    })
                    ->groupBy('payment_status')
                    ->pluck('total', 'payment_status'),
                'customer_type_count' => DB::table('bookings')
                    ->select(DB::raw('COALESCE(customer_type, "") as customer_type'), DB::raw('count(*) as total'))
                    ->when($request->has('start_date') && $request->has('end_date'), function($query) use ($request) {
                        return $query->whereBetween('booking_date', [
                            Carbon::parse($request->start_date)->startOfDay(),
                            Carbon::parse($request->end_date)->endOfDay()
                        ]);
                    })
                    ->groupBy('customer_type')
                    ->pluck('total', 'customer_type')
            ];

            // Ambil data dengan pagination
            $perPage = $request->input('per_page', 10);
            $bookings = $query->latest()->paginate($perPage);

            // Format data booking
            $formattedBookings = collect($bookings->items())->map(function($booking) {
                $departureTime = $booking->departure_time ? Carbon::parse($booking->departure_time) : null;
                $arrivalTime = $booking->arrival_time ? Carbon::parse($booking->arrival_time) : null;
                
                return [
                    'id' => $booking->id,
                    'booking_info' => [
                        'booker_name' => $booking->name,
                        'email' => $booking->email,
                        'phone' => $booking->phone_number,
                        'booking_date' => Carbon::parse($booking->booking_date)->format('d M Y H:i'),
                        'customer_type' => $booking->customer_type,
                        'created_by' => $booking->created_by_name ?? 'System'
                    ],
                    'schedule_info' => [
                        'route' => $booking->dari && $booking->ke ? $booking->dari . ' - ' . $booking->ke : 'N/A',
                        'bus_info' => [
                            'kode' => $booking->kode_bus ?? 'N/A',
                            'nama' => $booking->nama_bus ?? 'N/A',
                            'tipe' => $booking->tipe_bus ?? 'N/A',
                            'kelas' => $booking->kelas_bus ?? 'N/A'
                        ],
                        'departure_time' => $departureTime ? $departureTime->format('d M Y H:i') : 'N/A',
                        'arrival_time' => $arrivalTime ? $arrivalTime->format('d M Y H:i') : 'N/A',
                        'time_until_departure' => $departureTime ? $this->formatTimeUntilDeparture($departureTime) : 'N/A',
                        'status' => $departureTime ? ($departureTime->isPast() ? 'SELESAI' : 'AKAN DATANG') : 'N/A'
                    ],
                    'passengers' => collect($booking->passengers)->map(function($passenger) {
                        return [
                            'name' => $passenger->name,
                            'seat_number' => $passenger->seat_number ?? 'N/A',
                            'gender' => $passenger->gender == 'L' ? 'Laki-laki' : 'Perempuan',
                            'phone' => $passenger->phone_number
                        ];
                    }),
                    'payment_info' => [
                        'status' => $booking->payment_status,
                        'amount' => number_format($booking->final_price, 0, ',', '.'),
                        'payment_id' => $booking->payment_id,
                        'redirect_url' => $booking->payment_status === 'UNPAID' ? $booking->redirect_url : null
                    ]
                ];
            });

            return response()->json([
                'status' => true,
                'data' => $formattedBookings,
                'statistics' => $statistics,
                'pagination' => [
                    'current_page' => $bookings->currentPage(),
                    'total_pages' => $bookings->lastPage(),
                    'total_items' => $bookings->total(),
                    'per_page' => $bookings->perPage()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil data pesanan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $booking = Bookings::with([
                'passengers.scheduleSeat.seat',
                'schedule.route',
                'schedule.bus',
                'user',
                'createdBy'
            ])->findOrFail($id);

            // Cek apakah schedule ada
            if (!$booking->schedule) {
                $detailedBooking = [
                    'id' => $booking->id,
                    'booking_info' => [
                        'booker_name' => $booking->name,
                        'email' => $booking->email,
                        'phone' => $booking->phone_number,
                        'booking_date' => Carbon::parse($booking->booking_date)->format('d M Y H:i'),
                        'customer_type' => $booking->customer_type,
                        'created_by' => $booking->createdBy->name ?? 'System',
                        'created_at' => $booking->created_at->format('d M Y H:i:s'),
                        'updated_at' => $booking->updated_at->format('d M Y H:i:s')
                    ],
                    'schedule_info' => [
                        'route' => [
                            'name' => 'N/A',
                            'origin' => 'N/A',
                            'destination' => 'N/A'
                        ],
                        'bus' => [
                            'name' => 'N/A',
                            'plate_number' => 'N/A',
                            'capacity' => 'N/A'
                        ],
                        'departure_time' => 'N/A',
                        'time_until_departure' => 'N/A',
                        'status' => 'N/A'
                    ],
                    'passengers' => $booking->passengers->map(function($passenger) {
                        return [
                            'name' => $passenger->name,
                            'seat_number' => $passenger->scheduleSeat->seat->seat_number ?? 'N/A',
                            'gender' => $passenger->gender == 'L' ? 'Laki-laki' : 'Perempuan',
                            'phone' => $passenger->phone_number
                        ];
                    }),
                    'payment_info' => [
                        'status' => $booking->payment_status,
                        'amount' => number_format($booking->final_price, 0, ',', '.'),
                        'payment_id' => $booking->payment_id,
                        'payment_method' => $booking->payment_method ?? 'N/A'
                    ]
                ];

                return response()->json([
                    'status' => true,
                    'data' => $detailedBooking
                ]);
            }

            $departureTime = Carbon::parse($booking->schedule->departure_time);
            $timeUntilDeparture = now()->diffForHumans($departureTime, ['parts' => 2]);

            $detailedBooking = [
                'id' => $booking->id,
                'booking_info' => [
                    'booker_name' => $booking->name,
                    'email' => $booking->email,
                    'phone' => $booking->phone_number,
                    'booking_date' => Carbon::parse($booking->booking_date)->format('d M Y H:i'),
                    'customer_type' => $booking->customer_type,
                    'created_by' => $booking->createdBy->name ?? 'System',
                    'created_at' => $booking->created_at->format('d M Y H:i:s'),
                    'updated_at' => $booking->updated_at->format('d M Y H:i:s')
                ],
                'schedule_info' => [
                    'route' => [
                        'name' => $booking->schedule->route->name ?? 'N/A',
                        'origin' => $booking->schedule->route->origin ?? 'N/A',
                        'destination' => $booking->schedule->route->destination ?? 'N/A'
                    ],
                    'bus' => [
                        'name' => $booking->schedule->bus->name ?? 'N/A',
                        'plate_number' => $booking->schedule->bus->plate_number ?? 'N/A',
                        'capacity' => $booking->schedule->bus->capacity ?? 'N/A'
                    ],
                    'departure_time' => $departureTime->format('d M Y H:i'),
                    'time_until_departure' => $timeUntilDeparture,
                    'status' => $departureTime->isPast() ? 'SELESAI' : 'AKAN DATANG'
                ],
                'passengers' => $booking->passengers->map(function($passenger) {
                    return [
                        'name' => $passenger->name,
                        'seat_number' => $passenger->scheduleSeat->seat->seat_number ?? 'N/A',
                        'gender' => $passenger->gender == 'L' ? 'Laki-laki' : 'Perempuan',
                        'phone' => $passenger->phone_number
                    ];
                }),
                'payment_info' => [
                    'status' => $booking->payment_status,
                    'amount' => number_format($booking->final_price, 0, ',', '.'),
                    'payment_id' => $booking->payment_id,
                    'payment_method' => $booking->payment_method ?? 'N/A'
                ]
            ];

            return response()->json([
                'status' => true,
                'data' => $detailedBooking
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Gagal mengambil detail pesanan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancelBooking($id)
    {
        try {
            $booking = Bookings::findOrFail($id);

            // Update status pembayaran menjadi CANCELLED
            $booking->update([
                'payment_status' => 'CANCELLED'
            ]);

            // Update scheduleseats menjadi tersedia kembali
            DB::table('scheduleseats')
                ->where('booking_id', $booking->id)
                ->update([
                    'is_available' => 1,
                    'description' => 'Kursi tersedia kembali karena pembayaran dibatalkan'
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Booking berhasil dibatalkan',
                'data' => [
                    'booking_id' => $booking->id,
                    'payment_status' => 'CANCELLED',
                    'cancelled_at' => Carbon::now()->format('d M Y H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membatalkan booking: ' . $e->getMessage()
            ], 500);
        }
    }

    private function formatTimeUntilDeparture($departureTime)
    {
        if (!$departureTime) {
            return 'N/A';
        }

        $now = Carbon::now();
        $diff = $now->diff($departureTime);
        
        if ($departureTime->isPast()) {
            return $departureTime->diffForHumans();
        }

        $parts = [];
        if ($diff->d > 0) {
            $parts[] = $diff->d . ' hari';
        }
        if ($diff->h > 0) {
            $parts[] = $diff->h . ' jam';
        }
        if ($diff->i > 0 && count($parts) < 2) {
            $parts[] = $diff->i . ' menit';
        }

        return implode(' ', $parts) . ' mendatang';
    }
} 
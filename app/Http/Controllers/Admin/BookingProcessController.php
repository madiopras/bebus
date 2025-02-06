<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bookings;
use App\Models\ScheduleRute;
use App\Models\Passenger;
use App\Models\Seats;
use App\Models\Schedules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Models\Payments;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;

class BookingProcessController extends Controller
{
    private function sendWhatsAppMessage($to, $message)
    {
        try {
            // Format nomor telepon (hilangkan awalan 0 dan tambahkan 62)
            $phone = preg_replace('/^0/', '62', $to);
            
            $url = config('services.wablas.url') . '/send-message';
            $headers = [
                'Authorization' => config('services.wablas.token'),
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ];

            $payload = [
                'phone' => $phone,
                'message' => $message,
                'isGroup' => false
            ];

            $response = Http::withHeaders($headers)->post($url, $payload);

            if (!$response->successful()) {
                Log::error('Wablas API Error:', [
                    'status' => $response->status(),
                    'response' => $response->json()
                ]);
                return false;
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Wablas Exception:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    private function sendWhatsAppFile($to, $filePath, $caption)
    {
        try {
            // Format nomor telepon
            $phone = preg_replace('/^0/', '62', $to);
            
            // Cek apakah file ada
            if (!file_exists($filePath)) {
                throw new \Exception('File not found: ' . $filePath);
            }

            $token = config('services.wablas.token');
            $baseUrl = rtrim(env('WABLAS_URL', 'https://tegal.wablas.com/api'), '/');
            $endpoint = '/send-document';
            $url = $baseUrl . $endpoint;

            // Persiapkan data untuk curl
            $data = [
                'phone' => $phone,
                'document' => new \CURLFile($filePath),
                'caption' => $caption
            ];

            // Inisialisasi CURL
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: ' . $token
            ]);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

            // Eksekusi CURL
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            curl_close($ch);

            if ($httpCode !== 200) {
                throw new \Exception('Failed to send document. HTTP Code: ' . $httpCode . ', Response: ' . $response);
            }

            $responseData = json_decode($response, true);
            if (!$responseData || !isset($responseData['status']) || $responseData['status'] !== true) {
                throw new \Exception('Failed to send document: ' . ($responseData['message'] ?? 'Unknown error'));
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Wablas Document API Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'phone' => $to,
                'file_path' => $filePath
            ]);
            return false;
        }
    }

    private function generatePDF($booking)
    {
        try {
            // Ambil data schedule, bus, dan lokasi dengan join yang benar
            $scheduleRute = ScheduleRute::select(
                'schedule_rute.*',
                'schedule_rute.departure_time as schedule_rute_departure_time',
                'schedule_rute.arrival_time as schedule_rute_arrival_time',
                'buses.bus_number',
                'buses.bus_name',
                'classes.class_name',
                'start_loc.name as origin_name',
                'end_loc.name as destination_name'
            )
            ->join('schedules', 'schedule_rute.schedule_id', '=', 'schedules.id')
            ->join('buses', 'schedules.bus_id', '=', 'buses.id')
            ->join('classes', 'buses.class_id', '=', 'classes.id')
            ->join('routes', 'schedule_rute.route_id', '=', 'routes.id')
            ->join('locations as start_loc', 'routes.start_location_id', '=', 'start_loc.id')
            ->join('locations as end_loc', 'routes.end_location_id', '=', 'end_loc.id')
            ->where('schedule_rute.id', $booking->schedule_id)
            ->first();

            // Ambil data schedule seats dengan informasi lengkap
            $scheduleSeats = DB::table('scheduleseats')
                ->select(
                    'scheduleseats.*',
                    'seats.seat_number',
                    'passengers.name',
                    'passengers.gender',
                    'passengers.phone_number'
                )
                ->join('seats', 'seats.id', '=', 'scheduleseats.seat_id')
                ->join('passengers', 'passengers.id', '=', 'scheduleseats.passengers_id')
                ->where('scheduleseats.booking_Id', $booking->id)
                ->get();

            // Generate nama file
            $filename = 'ticket_' . $booking->payment_id . '.pdf';
            
            // Data untuk template
            $templateData = [
                'booking' => $booking,
                'scheduleSeats' => $scheduleSeats,
                'scheduleRute' => $scheduleRute
            ];
            
            // Generate PDF
            $pdf = PDF::loadView('pdf.ticket', $templateData);
            $pdf->setPaper('A4', 'portrait');
            
            // Pastikan direktori ada
            $directory = public_path('storage/tickets');
            if (!file_exists($directory)) {
                mkdir($directory, 0777, true);
            }
            
            $filePath = $directory . '/' . $filename;
            $pdf->save($filePath);
            
            return [
                'filename' => $filename,
                'path' => $filePath
            ];
            
        } catch (\Exception $e) {
            Log::error('Error in generatePDF:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function sendSuccessNotification($booking)
    {
        try {
            // Generate PDF
            $pdfInfo = $this->generatePDF($booking);
            
            // Format pesan WhatsApp
            $message = "✅ *Pembayaran Berhasil*\n\n"
                    . "Hai {$booking->name},\n"
                    . "Pembayaran tiket bus Anda telah berhasil dikonfirmasi.\n\n"
                    . "🎫 *Detail Booking*\n"
                    . "Booking ID: {$booking->payment_id}\n"
                    . "Total Pembayaran: Rp " . number_format($booking->final_price, 0, ',', '.') . "\n\n"
                    . "E-ticket Anda akan dikirim setelah pesan ini.\n\n"
                    . "Terima kasih telah menggunakan layanan kami! 🙏";

            // Kirim pesan teks
            $this->sendWhatsAppMessage($booking->phone_number, $message);

            // Tunggu sebentar sebelum mengirim file
            sleep(2);

            // Kirim file PDF
            $caption = "E-Ticket Bus - " . $booking->payment_id;
            $sent = $this->sendWhatsAppFile($booking->phone_number, $pdfInfo['path'], $caption);

            if ($sent) {
                // Hapus file PDF setelah berhasil dikirim
                if (file_exists($pdfInfo['path'])) {
                    unlink($pdfInfo['path']);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error sending success notification:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'booking_id' => $booking->id
            ]);
            
            // Pastikan file PDF dihapus meskipun terjadi error
            if (isset($pdfInfo) && file_exists($pdfInfo['path'])) {
                unlink($pdfInfo['path']);
            }
        }
    }

    public function store(Request $request)
    {
        // Validasi request
        $validator = Validator::make($request->all(), [
            'schedule_rute_id' => 'required|exists:schedule_rute,id',
            'booker.name' => 'required|string|max:255',
            'booker.email' => 'required|email|max:255',
            'booker.phone' => 'required|string|max:20',
            'passengers' => 'required|array|min:1',
            'passengers.*.seat_number' => 'required|integer',
            'passengers.*.name' => 'required|string|max:255',
            'passengers.*.phone' => 'nullable|string|max:20',
            'passengers.*.gender' => 'required|in:L,P',
            'payment_method' => 'required|string|in:TUNAI,TRANSFER,WHATSAPP',
            'customer_type' => 'required|string|in:ADMIN,CUSTOMER'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Ambil data schedule_rute
            $scheduleRute = ScheduleRute::with('schedule')->findOrFail($request->schedule_rute_id);
            
            // Hitung total harga
            $totalPrice = count($request->passengers) * $scheduleRute->price_rute;

            // Set customer_type berdasarkan role
            $customerType = $request->user()->hasRole('user') ? 'CUSTOMER' : 'ADMIN';

            // Buat booking baru
            $booking = Bookings::create([
                'user_id' => $request->user()->id,
                'schedule_id' => $request->schedule_rute_id,
                'name' => $request->booker['name'],
                'email' => $request->booker['email'],
                'phone_number' => $request->booker['phone'],
                'booking_date' => Carbon::now(),
                'payment_status' => 'UNPAID',
                'final_price' => $totalPrice,
                'created_by_id' => $request->user() ? $request->user()->id : null,
                'updated_by_id' => $request->user() ? $request->user()->id : null,
                'customer_type' => $customerType
            ]);

            // Simpan data penumpang
            foreach ($request->passengers as $passenger) {
                // Ambil seat ID berdasarkan nomor kursi
                $seat = Seats::where('bus_id', $scheduleRute->schedule->bus_id)
                            ->where('seat_number', $passenger['seat_number'])
                            ->first();

                if (!$seat) {
                    throw new \Exception('Kursi tidak ditemukan: ' . $passenger['seat_number']);
                }

                // Cek apakah kursi sudah dipesan
                $existingPassenger = Passenger::where('schedule_seat_id', $seat->id)
                    ->whereHas('booking', function($query) use ($scheduleRute) {
                        $query->where('schedule_id', $scheduleRute->id)
                              ->where('payment_status', '!=', 'CANCELLED');
                    })->first();

                if ($existingPassenger) {
                    throw new \Exception('Kursi ' . $passenger['seat_number'] . ' sudah dipesan');
                }

                $passenger = Passenger::create([
                    'booking_id' => $booking->id,
                    'schedule_seat_id' => $seat->id,
                    'name' => $passenger['name'],
                    'phone_number' => $passenger['phone'] ?? null,
                    'gender' => $passenger['gender'],
                    'created_by_id' => $request->user() ? $request->user()->id : null,
                    'updated_by_id' => $request->user() ? $request->user()->id : null
                ]);

                // Tambahkan data ke scheduleseats
                \App\Models\ScheduleSeats::create([
                    'schedule_id' => $scheduleRute->schedule_id,
                    'booking_Id' => $booking->id,
                    'seat_id' => $seat->id,
                    'schedule_rute_id' => $scheduleRute->id,
                    'passengers_id' => $passenger->id,
                    'is_available' => 0, // 0 karena sedang dipesan
                    'description' => 'Kursi dipesan oleh ' . $passenger['name'],
                    'created_by_id' => $request->user() ? $request->user()->id : null,
                    'updated_by_id' => $request->user() ? $request->user()->id : null
                ]);
            }

            // Jika metode pembayaran TUNAI, buat record payment
            if ($request->payment_method === 'TUNAI') {
                // Generate payment ID untuk TUNAI
                $randomNumber = str_pad(mt_rand(1, 9999999999), 10, '0', STR_PAD_LEFT);
                $paymentId = 'TUNAI-' . $booking->id . '-' . $randomNumber;

                $payment = Payments::create([
                    'booking_id' => $booking->id,
                    'payment_method' => $request->payment_method,
                    'payment_date' => Carbon::now(),
                    'amount' => $totalPrice,
                    'created_by_id' => $request->user() ? $request->user()->id : null,
                    'updated_by_id' => $request->user() ? $request->user()->id : null
                ]);

                // Update payment_id dan status pembayaran pada booking
                $booking->update([
                    'payment_id' => $paymentId,
                    'payment_status' => 'PAID'
                ]);

                // Kirim notifikasi dan tiket
                $this->sendSuccessNotification($booking);
            }

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Booking berhasil dibuat',
                'data' => $booking->load('passengers')
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
} 
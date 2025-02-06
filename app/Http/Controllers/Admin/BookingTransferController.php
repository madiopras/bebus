<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Bookings;
use App\Models\ScheduleRute;
use App\Models\Passenger;
use App\Models\Seats;
use App\Models\Schedules;
use App\Models\MidtransLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Midtrans\Config;
use Midtrans\Snap;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class BookingTransferController extends Controller
{
    private function sendWhatsAppMessage($to, $message)
    {
        try {
            Log::info('Attempting to send WhatsApp message', [
                'to' => $to,
                'message_length' => strlen($message)
            ]);

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

            Log::info('Wablas API Response', [
                'status_code' => $response->status(),
                'body' => $response->json()
            ]);

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

    public function handlePaymentNotification(Request $request)
    {
        try {
            Log::info('Midtrans Notification Received:', $request->all());
            
            $notificationBody = $request->all();
            
            // Verifikasi signature key
            Config::$isProduction = config('midtrans.is_production');
            Config::$serverKey = config('midtrans.server_key');
            
            $orderId = $notificationBody['order_id'];
            $statusCode = $notificationBody['status_code'];
            $grossAmount = $notificationBody['gross_amount'];
            $serverKey = Config::$serverKey;
            
            // Verifikasi signature key
            $input = $orderId . $statusCode . $grossAmount . $serverKey;
            $signature = hash('sha512', $input);
            
            if ($signature !== ($notificationBody['signature_key'] ?? '')) {
                throw new \Exception('Signature key tidak valid');
            }
            
            // Ambil ID booking dari order_id (format: BOOK-{id}-timestamp)
            preg_match('/BOOK-(\d+)-/', $orderId, $matches);
            if (empty($matches[1])) {
                throw new \Exception('Format order ID tidak valid');
            }
            
            $bookingId = $matches[1];
            $booking = Bookings::findOrFail($bookingId);

            // Validasi jumlah pembayaran
            if ((float)$grossAmount != (float)$booking->final_price) {
                throw new \Exception('Jumlah pembayaran tidak sesuai');
            }

            // Tentukan payment status
            $paymentStatus = 'PENDING';
            switch ($notificationBody['transaction_status'] ?? '') {
                case 'capture':
                    if (($notificationBody['fraud_status'] ?? '') == 'challenge') {
                        $paymentStatus = 'CHALLENGE';
                    } else if (($notificationBody['fraud_status'] ?? '') == 'accept') {
                        $paymentStatus = 'PAID';
                    }
                    break;
                    case 'settlement':
                        $paymentStatus = 'PAID';
                        break;
                    case 'pending':
                        $paymentStatus = 'UNPAID';
                        break;
                    case 'deny':
                        $paymentStatus = 'CANCELLED';
                        break;
                    case 'cancel':
                        $paymentStatus = 'CANCELLED';
                        break;
                    case 'expire':
                        $paymentStatus = 'CANCELLED';
                        break;
                    case 'failure':
                        $paymentStatus = 'CANCELLED';
                        break;
                    case 'refund':
                        $paymentStatus = 'CANCELLED';
                        break;
            }

            // Simpan log Midtrans
            MidtransLog::create([
                'order_id' => $orderId,
                'booking_id' => $bookingId,
                'transaction_status' => $notificationBody['transaction_status'] ?? '',
                'payment_status' => $paymentStatus,
                'midtrans_response' => $notificationBody
            ]);

            // Update status booking
            $booking->payment_status = $paymentStatus;
            $booking->save();

            // Kirim notifikasi sesuai status
            if ($paymentStatus == 'PAID') {
                $this->sendSuccessNotification($booking);
            } else if (in_array($paymentStatus, ['CANCELLED', 'EXPIRED'])) {
                $this->sendFailureNotification($booking);
            }
            
            Log::info('Payment status updated:', [
                'booking_id' => $booking->id,
                'status' => $booking->payment_status
            ]);
            
            return response()->json(['status' => true]);
            
        } catch (\Exception $e) {
            Log::error('Midtrans Notification Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Notification processing failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function generatePDF($booking)
    {
        try {
            Log::info('Starting PDF generation', [
                'booking_id' => $booking->id,
                'payment_id' => $booking->payment_id
            ]);

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

            if (!$scheduleRute) {
                throw new \Exception('Schedule route not found for booking ID: ' . $booking->id);
            }

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

            Log::info('Data loaded:', [
                'booking' => $booking->toArray(),
                'schedule_seats' => $scheduleSeats,
                'schedule_rute' => $scheduleRute->toArray()
            ]);
            
            // Generate nama file
            $filename = 'ticket_' . $booking->payment_id . '.pdf';
            
            // Data untuk template
            $templateData = [
                'booking' => $booking,
                'scheduleSeats' => $scheduleSeats,
                'scheduleRute' => $scheduleRute
            ];
            
            Log::info('Template data:', $templateData);
            
            try {
                // Generate PDF dengan margin yang sesuai
                $pdf = PDF::loadView('pdf.ticket', $templateData);
                $pdf->setPaper('A4', 'portrait');
                
                // Pastikan direktori ada
                $directory = public_path('storage/tickets');
                if (!file_exists($directory)) {
                    mkdir($directory, 0777, true);
                }
                
                Log::info('Directory check:', [
                    'directory' => $directory,
                    'exists' => file_exists($directory),
                    'writable' => is_writable($directory)
                ]);
                
                $filePath = $directory . '/' . $filename;
                $pdfOutput = $pdf->output();
                
                Log::info('PDF output generated', [
                    'output_size' => strlen($pdfOutput)
                ]);
                
                file_put_contents($filePath, $pdfOutput);
                
                if (!file_exists($filePath)) {
                    throw new \Exception('Failed to save PDF file: ' . $filePath);
                }
                
                Log::info('PDF file saved successfully', [
                    'path' => $filePath,
                    'size' => filesize($filePath)
                ]);
                
                return [
                    'filename' => $filename,
                    'path' => $filePath
                ];
                
            } catch (\Exception $e) {
                Log::error('PDF generation error:', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }
            
        } catch (\Exception $e) {
            Log::error('Error in generatePDF:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function testGeneratePDF($bookingId)
    {
        try {
            Log::info('Starting test PDF generation', [
                'booking_id' => $bookingId
            ]);

            // Cari booking dengan eager loading yang benar
            $booking = Bookings::with(['user', 'createdBy'])->findOrFail($bookingId);

            // Generate PDF dan dapatkan informasi file
            $pdfInfo = $this->generatePDF($booking);

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

            if (!$scheduleRute) {
                throw new \Exception('Schedule route not found for booking ID: ' . $booking->id);
            }

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

            // Format data untuk response
            $responseData = [
                'status' => true,
                'message' => 'PDF generated successfully',
                'pdf_info' => [
                    'filename' => $pdfInfo['filename'],
                    'path' => $pdfInfo['path'],
                    'file_exists' => file_exists($pdfInfo['path']),
                    'file_size' => filesize($pdfInfo['path']),
                    'public_url' => url('storage/tickets/' . $pdfInfo['filename'])
                ],
                'ticket_data' => [
                    'id' => $booking->id,
                    'booking_info' => [
                        'booker_name' => $booking->name,
                        'email' => $booking->email,
                        'phone' => $booking->phone_number,
                        'booking_date' => Carbon::parse($booking->created_at)->format('d M Y H:i'),
                        'customer_type' => $booking->customer_type,
                        'created_by' => $booking->createdBy ? $booking->createdBy->name : null
                    ],
                    'schedule_info' => [
                        'route' => $scheduleRute->origin_name . ' - ' . $scheduleRute->destination_name,
                        'bus_info' => [
                            'kode' => $scheduleRute->bus_number,
                            'nama' => $scheduleRute->bus_name,
                            'tipe' => 'SHD Bus',
                            'kelas' => $scheduleRute->class_name
                        ],
                        'departure_time' => Carbon::parse($scheduleRute->schedule_rute_departure_time)->format('d M Y H:i'),
                        'arrival_time' => Carbon::parse($scheduleRute->schedule_rute_arrival_time)->format('d M Y H:i'),
                        'time_until_departure' => Carbon::parse($scheduleRute->schedule_rute_departure_time)->diffForHumans([
                            'syntax' => \Carbon\CarbonInterface::DIFF_ABSOLUTE,
                            'parts' => 2
                        ]),
                        'status' => Carbon::parse($scheduleRute->schedule_rute_departure_time)->isFuture() ? 'AKAN DATANG' : 'SELESAI'
                    ],
                    'passengers' => $scheduleSeats->map(function($seat) {
                        return [
                            'name' => $seat->name,
                            'seat_number' => str_pad($seat->seat_number, 2, '0', STR_PAD_LEFT),
                            'gender' => $seat->gender == 'L' ? 'Laki-laki' : 'Perempuan',
                            'phone' => $seat->phone_number
                        ];
                    }),
                    'payment_info' => [
                        'status' => $booking->payment_status,
                        'amount' => number_format($booking->final_price, 0, ',', '.'),
                        'payment_id' => $booking->payment_id,
                        'redirect_url' => $booking->redirect_url,
                        'payment_method' => $booking->payment_method,
                        'refund_info' => null
                    ]
                ]
            ];

            return response()->json($responseData);

        } catch (\Exception $e) {
            Log::error('Test PDF generation failed:', [
                'booking_id' => $bookingId,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Failed to generate PDF: ' . $e->getMessage(),
                'error_details' => [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ]
            ], 500);
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

            // Persiapkan file untuk dikirim
            $fileContent = file_get_contents($filePath);
            if ($fileContent === false) {
                throw new \Exception('Failed to read file: ' . $filePath);
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

            // Log request untuk debugging
            Log::info('Sending document to Wablas:', [
                'url' => $url,
                'phone' => $phone,
                'file_path' => $filePath,
                'caption' => $caption
            ]);

            // Eksekusi CURL
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            
            // Log response untuk debugging
            Log::info('Wablas Document API Response:', [
                'http_code' => $httpCode,
                'response' => $response,
                'curl_error' => curl_error($ch)
            ]);

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

    private function sendSuccessNotification($booking)
    {
        try {
            Log::info('Starting success notification process for booking', [
                'booking_id' => $booking->id,
                'payment_id' => $booking->payment_id
            ]);

            // Generate PDF
            $pdfInfo = $this->generatePDF($booking);
            
            // Path file PDF
            $pdfPath = public_path('storage/tickets/ticket_' . $booking->payment_id . '.pdf');
            
            // Verifikasi file PDF
            if (!file_exists($pdfPath)) {
                throw new \Exception('PDF file not found after generation: ' . $pdfPath);
            }
            
            Log::info('PDF verification', [
                'path' => $pdfPath,
                'exists' => file_exists($pdfPath),
                'size' => filesize($pdfPath)
            ]);
            
            // Format pesan WhatsApp
            $message = "✅ *Pembayaran Berhasil*\n\n"
                    . "Hai {$booking->name},\n"
                    . "Pembayaran tiket bus Anda telah berhasil dikonfirmasi.\n\n"
                    . "🎫 *Detail Booking*\n"
                    . "Booking ID: {$booking->payment_id}\n"
                    . "Total Pembayaran: Rp " . number_format($booking->final_price, 0, ',', '.') . "\n\n"
                    . "E-ticket Anda akan dikirim setelah pesan ini.\n\n"
                    . "Terima kasih telah menggunakan layanan kami! 🙏";

            // Kirim pesan teks terlebih dahulu
            $this->sendWhatsAppMessage($booking->phone_number, $message);

            // Tunggu sebentar sebelum mengirim file
            sleep(2);

            // Kirim file PDF
            $caption = "E-Ticket Bus - " . $booking->payment_id;
            $sent = $this->sendWhatsAppFile($booking->phone_number, $pdfPath, $caption);

            if (!$sent) {
                Log::error('Failed to send PDF ticket', [
                    'booking_id' => $booking->id,
                    'pdf_path' => $pdfPath
                ]);
            } else {
                // Hapus file PDF setelah berhasil dikirim
                if (file_exists($pdfPath)) {
                    unlink($pdfPath);
                    Log::info('PDF file deleted successfully', [
                        'pdf_path' => $pdfPath
                    ]);
                }
            }

        } catch (\Exception $e) {
            Log::error('Error sending success notification:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'booking_id' => $booking->id
            ]);
            
            // Pastikan file PDF dihapus meskipun terjadi error
            $pdfPath = public_path('storage/tickets/ticket_' . $booking->payment_id . '.pdf');
            if (file_exists($pdfPath)) {
                unlink($pdfPath);
                Log::info('PDF file deleted after error', [
                    'pdf_path' => $pdfPath
                ]);
            }
        }
    }

    private function sendFailureNotification($booking)
    {
        $message = "❌ *Pembayaran Gagal*\n\n"
                . "Hai {$booking->name},\n"
                . "Mohon maaf, pembayaran tiket bus Anda tidak berhasil.\n\n"
                . "🎫 *Detail Booking*\n"
                . "Booking ID: {$booking->payment_id}\n"
                . "Total: Rp " . number_format($booking->final_price, 0, ',', '.') . "\n\n"
                . "Silakan lakukan pemesanan ulang. Terima kasih! 🙏";
        $this->sendWhatsAppMessage($booking->phone_number, $message);
    }

    public function handlePaymentFinish(Request $request)
    {
        try {
            Log::info('Payment Finish:', $request->all());
            
            $orderId = $request->order_id;
            preg_match('/BOOK-(\d+)-/', $orderId, $matches);
            if (empty($matches[1])) {
                throw new \Exception('Format order ID tidak valid');
            }
            
            $bookingId = $matches[1];
            $booking = Bookings::findOrFail($bookingId);

            // Konfigurasi Midtrans
            Config::$serverKey = config('midtrans.server_key');
            Config::$isProduction = config('midtrans.is_production');

            // Validasi status transaksi ke Midtrans
            try {
                $midtransStatus = \Midtrans\Transaction::status($orderId);
                Log::info('Midtrans Status Response:', (array) $midtransStatus);

                // Konversi response ke array
                $midtransStatusArray = (array) $midtransStatus;

                // Tentukan payment status berdasarkan response Midtrans
                $paymentStatus = 'PENDING';
                switch ($midtransStatusArray['transaction_status']) {
                    case 'capture':
                        if ($midtransStatusArray['fraud_status'] == 'challenge') {
                            $paymentStatus = 'CHALLENGE';
                        } else if ($midtransStatusArray['fraud_status'] == 'accept') {
                            $paymentStatus = 'PAID';
                        }
                        break;
                        case 'settlement':
                            $paymentStatus = 'PAID';
                            break;
                        case 'pending':
                            $paymentStatus = 'UNPAID';
                            break;
                        case 'deny':
                            $paymentStatus = 'CANCELLED';
                            break;
                        case 'cancel':
                            $paymentStatus = 'CANCELLED';
                            break;
                        case 'expire':
                            $paymentStatus = 'CANCELLED';
                            break;
                        case 'failure':
                            $paymentStatus = 'CANCELLED';
                            break;
                        case 'refund':
                            $paymentStatus = 'CANCELLED';
                            break;
                }

                // Update status booking
                $booking->payment_status = $paymentStatus;
                $booking->save();

                // Simpan log Midtrans
                MidtransLog::create([
                    'order_id' => $orderId,
                    'booking_id' => $bookingId,
                    'transaction_status' => $midtransStatusArray['transaction_status'],
                    'payment_status' => $paymentStatus,
                    'midtrans_response' => $midtransStatusArray
                ]);

                // Kirim notifikasi sesuai status
                if ($paymentStatus == 'PAID') {
                    $this->sendSuccessNotification($booking);
                } else if (in_array($paymentStatus, ['CANCELLED', 'EXPIRED'])) {
                    $this->sendFailureNotification($booking);
                }

                // Redirect sesuai payment_status
                if ($paymentStatus == 'PAID') {
                    return redirect()->away(config('app.frontend_url') . "/id/checkpayment/{$orderId}?status=success");
                } else if ($paymentStatus == 'CANCELLED') {
                    return redirect()->away(config('app.frontend_url') . "/id/checkpayment/{$orderId}?status=error");
                } else {
                    return redirect()->away(config('app.frontend_url') . "/id/checkpayment/{$orderId}?status=unfinish");
                }

            } catch (\Exception $e) {
                Log::error('Midtrans Status Check Error:', [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                throw new \Exception('Gagal memvalidasi status pembayaran: ' . $e->getMessage());
            }

        } catch (\Exception $e) {
            Log::error('Payment Finish Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return redirect()->away(config('app.frontend_url') . "/id/checkpayment/{$request->order_id}?status=error");
        }
    }

    public function handlePaymentUnfinish(Request $request)
    {
        try {
            Log::info('Payment Unfinish:', $request->all());
            
            $orderId = $request->order_id;
            preg_match('/BOOK-(\d+)-/', $orderId, $matches);
            if (empty($matches[1])) {
                throw new \Exception('Format order ID tidak valid');
            }
            
            $bookingId = $matches[1];
            $booking = Bookings::findOrFail($bookingId);

            MidtransLog::create([
                'order_id' => $orderId,
                'booking_id' => $bookingId,
                'transaction_status' => 'UNPAID',
                'payment_status' => 'UNPAID',
                'midtrans_response' => $request->all()
            ]);

            return redirect()->away(config('app.frontend_url') . "/id/checkpayment/{$orderId}?status=unfinish");
        } catch (\Exception $e) {
            Log::error('Payment Unfinish Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return redirect()->away(config('app.frontend_url') . "/id/checkpayment/{$request->order_id}?status=error");
        }
    }

    public function handlePaymentError(Request $request)
    {
        try {
            Log::info('Payment Error:', $request->all());
            
            $orderId = $request->order_id;
            preg_match('/BOOK-(\d+)-/', $orderId, $matches);
            if (empty($matches[1])) {
                throw new \Exception('Format order ID tidak valid');
            }
            
            $bookingId = $matches[1];
            $booking = Bookings::findOrFail($bookingId);

            MidtransLog::create([
                'order_id' => $orderId,
                'booking_id' => $bookingId,
                'transaction_status' => 'ERROR',
                'payment_status' => 'ERROR',
                'midtrans_response' => $request->all()
            ]);

            return redirect()->away(config('app.frontend_url') . "/id/checkpayment/{$orderId}?status=error");
        } catch (\Exception $e) {
            Log::error('Payment Error Handler:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return redirect()->away(config('app.frontend_url') . "/id/checkpayment/{$request->order_id}?status=error");
        }
    }

    public function store(Request $request)
    {
        // Log request data
        Log::info('Booking Request:', [
            'data' => $request->all()
        ]);

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
            'passengers.*.birth_date' => 'nullable|date',
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
            
            // Log schedule_rute data
            Log::info('Schedule Rute:', [
                'data' => $scheduleRute->toArray()
            ]);
            
            // Hitung total harga
            $totalPrice = count($request->passengers) * $scheduleRute->price_rute;

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
                'customer_type' => $request->customer_type
            ]);

            // Log booking data
            Log::info('Booking Created:', [
                'booking' => $booking->toArray()
            ]);

            // Simpan data penumpang
            foreach ($request->passengers as $passenger) {
                // Ambil seat ID berdasarkan nomor kursi
                $seat = Seats::where('bus_id', $scheduleRute->schedule->bus_id)
                            ->where('seat_number', $passenger['seat_number'])
                            ->first();

                // Log seat data
                Log::info('Seat Data:', [
                    'bus_id' => $scheduleRute->schedule->bus_id,
                    'seat_number' => $passenger['seat_number'],
                    'seat' => $seat ? $seat->toArray() : null
                ]);

                if (!$seat) {
                    throw new \Exception('Kursi tidak ditemukan: ' . $passenger['seat_number']);
                }

                // Cek apakah kursi sudah dipesan
                $existingPassenger = Passenger::where('schedule_seat_id', $seat->id)
                    ->whereHas('booking', function($query) use ($scheduleRute) {
                        $query->where('schedule_id', $scheduleRute->id)
                              ->where('payment_status', '!=', 'CANCELLED');
                    })->first();

                // Log existing passenger
                Log::info('Existing Passenger:', [
                    'schedule_seat_id' => $seat->id,
                    'schedule_id' => $scheduleRute->schedule_id,
                    'passenger' => $existingPassenger ? $existingPassenger->toArray() : null
                ]);

                if ($existingPassenger) {
                    throw new \Exception('Kursi ' . $passenger['seat_number'] . ' sudah dipesan');
                }

                $passenger = Passenger::create([
                    'booking_id' => $booking->id,
                    'schedule_seat_id' => $seat->id,
                    'name' => $passenger['name'],
                    'gender' => $passenger['gender'],
                    'phone_number' => $passenger['phone'] ?? null,
                    'birth_date' => isset($passenger['birth_date']) ? $passenger['birth_date'] : null,
                    'description' => isset($passenger['description']) ? $passenger['description'] : null,
                    'created_by_id' => $request->user() ? $request->user()->id : null,
                    'updated_by_id' => $request->user() ? $request->user()->id : null
                ]);

                // Log passenger data
                Log::info('Passenger Created:', [
                    'passenger' => $passenger->toArray()
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

                // Log schedule seats data
                Log::info('Schedule Seats Created:', [
                    'schedule_id' => $scheduleRute->schedule_id,
                    'booking_id' => $booking->id,
                    'seat_id' => $seat->id,
                    'schedule_rute_id' => $scheduleRute->id,
                    'passengers_id' => $passenger->id
                ]);
            }

            // Konfigurasi Midtrans
            Config::$serverKey = config('midtrans.server_key');
            Config::$isProduction = config('midtrans.is_production');
            Config::$isSanitized = true;
            Config::$is3ds = true;

            // Siapkan item details untuk Midtrans
            $itemDetails = [];
            foreach ($request->passengers as $index => $passenger) {
                $itemDetails[] = [
                    'id' => 'SEAT-' . $passenger['seat_number'],
                    'price' => $scheduleRute->price_rute,
                    'quantity' => 1,
                    'name' => 'Tiket Bus - Kursi ' . $passenger['seat_number']
                ];
            }

            // Data transaksi Midtrans
            $transactionDetails = [
                'transaction_details' => [
                    'order_id' => 'BOOK-' . $booking->id . '-' . time(),
                    'gross_amount' => $totalPrice
                ],
                'customer_details' => [
                    'first_name' => $request->booker['name'],
                    'email' => $request->booker['email'],
                    'phone' => $request->booker['phone']
                ],
                'item_details' => $itemDetails,
                'enabled_payments' => [
                    'credit_card',
                    'cimb_clicks',
                    'bca_klikbca',
                    'bca_klikpay',
                    'bri_epay',
                    'echannel',
                    'permata_va',
                    'bca_va',
                    'bni_va',
                    'bri_va',
                    'cimb_va',
                    'other_va',
                    'gopay',
                    'indomaret',
                    'danamon_online',
                    'akulaku',
                    'shopeepay',
                    'kredivo',
                    'uob_ezpay',
                    'alfamart'
                ],
                'expiry' => [
                    'unit' => 'minute',
                    'duration' => 8
                ],
                'callbacks' => [
                    'finish' => url('/api/midtrans/finish'),
                    'error' => url('/api/midtrans/error'),
                    'notification' => url('/api/midtrans/notification')
                ]
            ];

            // Log transaction details
            Log::info('Midtrans Transaction Details:', [
                'transaction' => $transactionDetails
            ]);

            // Membuat Snap Token
            $snapToken = Snap::getSnapToken($transactionDetails);

            // Log snap token
            Log::info('Midtrans Snap Token:', [
                'token' => $snapToken
            ]);

            // Update booking dengan snap token
            $booking->update([
                'payment_id' => $transactionDetails['transaction_details']['order_id'],
                'redirect_url' => 'https://app.' . (config('midtrans.is_production') ? '' : 'sandbox.') . 'midtrans.com/snap/v2/vtweb/' . $snapToken
            ]);

            // Log booking update
            Log::info('Booking Updated:', [
                'booking' => $booking->fresh()->toArray()
            ]);

            // Format pesan WhatsApp
            $message = "🚌 *Konfirmasi Booking Bus*\n\n"
                    . "Hai {$request->booker['name']},\n"
                    . "Terima kasih telah melakukan pemesanan tiket bus. Berikut detail pesanan Anda:\n\n"
                    . "🎫 *Detail Booking*\n"
                    . "Booking ID: {$booking->payment_id}\n"
                    . "Total Pembayaran: Rp " . number_format($totalPrice, 0, ',', '.') . "\n"
                    . "Jumlah Kursi: " . count($request->passengers) . "\n\n"
                    . "💳 *Instruksi Pembayaran*\n"
                    . "1. Klik link pembayaran di bawah ini\n"
                    . "2. Pilih metode pembayaran yang tersedia\n"
                    . "3. Lakukan pembayaran sesuai instruksi\n\n"
                    . "🔗 Link Pembayaran:\n"
                    . "https://app." . (config('midtrans.is_production') ? '' : 'sandbox.') . "midtrans.com/snap/v2/vtweb/" . $snapToken . "\n\n"
                    . "⚠️ *PENTING*\n"
                    . "• Pembayaran akan kadaluarsa dalam 24 jam\n"
                    . "• Kursi Anda belum terkonfirmasi sebelum pembayaran selesai\n\n"
                    . "Jika ada pertanyaan, silakan hubungi kami di 085600121760\n\n"
                    . "Terima kasih! 🙏";

            // Log WhatsApp message
            Log::info('WhatsApp Message:', [
                'phone' => $booking->phone_number,
                'message' => $message
            ]);

            // Kirim pesan WhatsApp
            $this->sendWhatsAppMessage($booking->phone_number, $message);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Booking berhasil dibuat',
                'data' => [
                    'booking' => $booking->load('passengers'),
                    'payment' => [
                        'token' => $snapToken,
                        'redirect_url' => 'https://app.' . (config('midtrans.is_production') ? '' : 'sandbox.') . 'midtrans.com/snap/v2/vtweb/' . $snapToken
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Booking Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function processTransfer(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'status_code' => 'required|string',
                'status_message' => 'required|string',
                'transaction_id' => 'required|string',
                'order_id' => 'required|string',
                'gross_amount' => 'required',
                'payment_type' => 'required|string',
                'transaction_time' => 'required',
                'transaction_status' => 'required|string',
                'fraud_status' => 'nullable|string',
                'bank' => 'nullable|string',
                'masked_card' => 'nullable|string',
                'card_type' => 'nullable|string',
                'finish_redirect_url' => 'nullable|string',
                'booking_id' => 'required|exists:bookings,id',
                'status' => 'required|string',
                'signature_key' => 'required|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verifikasi signature key
            Config::$isProduction = config('midtrans.is_production');
            Config::$serverKey = config('midtrans.server_key');

            $orderId = $request->order_id;
            $statusCode = $request->status_code;
            $grossAmount = $request->gross_amount;
            $serverKey = Config::$serverKey;
            $input = $orderId . $statusCode . $grossAmount . $serverKey;
            $calculatedSignature = hash('sha512', $input);

            // Verifikasi signature
            if ($calculatedSignature !== $request->signature_key) {
                throw new \Exception('Signature key tidak valid');
            }

            $notificationBody = $request->all();
            $bookingId = $request->booking_id;
            
            $booking = Bookings::findOrFail($bookingId);

            // Simpan log Midtrans
            MidtransLog::create([
                'order_id' => $orderId,
                'status_code' => $statusCode,
                'status_message' => $request->status_message,
                'transaction_id' => $request->transaction_id,
                'gross_amount' => $grossAmount,
                'payment_type' => $request->payment_type,
                'transaction_time' => Carbon::parse($request->transaction_time),
                'transaction_status' => $request->transaction_status,
                'fraud_status' => $request->fraud_status,
                'bank' => $request->bank,
                'masked_card' => $request->masked_card,
                'card_type' => $request->card_type,
                'finish_redirect_url' => $request->finish_redirect_url,
                'booking_id' => $bookingId,
                'status' => $request->status,
                'raw_response' => $notificationBody
            ]);
            
            // Validasi jumlah pembayaran
            if ($grossAmount != $booking->final_price) {
                throw new \Exception('Jumlah pembayaran tidak sesuai');
            }

            // Update status pembayaran berdasarkan transaction_status dari Midtrans
            switch ($request->transaction_status) {
                case 'capture': // Untuk kartu kredit yang berhasil
                case 'settlement': // Untuk pembayaran yang sudah selesai
                    $booking->payment_status = 'PAID';
                    // Update scheduleseats menjadi tidak tersedia
                    \App\Models\ScheduleSeats::where('booking_Id', $booking->id)
                        ->update([
                            'is_available' => 0,
                            'description' => 'Kursi telah dibayar',
                            'updated_by_id' => $request->user() ? $request->user()->id : null
                        ]);
                    $message = "✅ *Pembayaran Berhasil*\n\n"
                            . "Hai {$booking->name},\n"
                            . "Pembayaran tiket bus Anda telah berhasil dikonfirmasi.\n\n"
                            . "🎫 *Detail Booking*\n"
                            . "Booking ID: {$booking->payment_id}\n"
                            . "Total Pembayaran: Rp " . number_format($booking->final_price, 0, ',', '.') . "\n\n"
                            . "Terima kasih telah menggunakan layanan kami! 🙏";
                    $this->sendWhatsAppMessage($booking->phone_number, $message);
                    break;
                    
                case 'pending':
                    $booking->payment_status = 'UNPAID';
                    break;
                    
                case 'deny':
                case 'cancel':
                case 'expire':
                case 'failure':
                    $booking->payment_status = 'CANCELLED';
                    // Update scheduleseats menjadi tersedia kembali
                    \App\Models\ScheduleSeats::where('booking_Id', $booking->id)
                        ->update([
                            'is_available' => 1,
                            'description' => 'Kursi tersedia kembali karena pembayaran dibatalkan',
                            'updated_by_id' => $request->user() ? $request->user()->id : null
                        ]);
                    $message = "❌ *Pembayaran Gagal*\n\n"
                            . "Hai {$booking->name},\n"
                            . "Mohon maaf, pembayaran tiket bus Anda tidak berhasil.\n\n"
                            . "🎫 *Detail Booking*\n"
                            . "Booking ID: {$booking->payment_id}\n"
                            . "Total: Rp " . number_format($booking->final_price, 0, ',', '.') . "\n\n"
                            . "Silakan lakukan pemesanan ulang. Terima kasih! 🙏";
                    $this->sendWhatsAppMessage($booking->phone_number, $message);
                    break;
            }
            
            $booking->save();
            
            return response()->json([
                'status' => true,
                'message' => 'Pembayaran berhasil diproses',
                'data' => [
                    'booking' => $booking,
                    'payment_status' => $booking->payment_status
                ]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Proses Transfer Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'status' => false,
                'message' => 'Gagal memproses pembayaran: ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeGuest(Request $request)
    {
        // Log request data
        Log::info('Guest Booking Request:', [
            'data' => $request->all()
        ]);

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
            'passengers.*.birth_date' => 'nullable|date',
            'customer_type' => 'required|string|in:ADMIN,CUSTOMER,GUEST',
            'payment_method' => 'required|string'
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
            
            // Log schedule_rute data
            Log::info('Schedule Rute:', [
                'data' => $scheduleRute->toArray()
            ]);
            
            // Hitung total harga
            $basePrice = count($request->passengers) * $scheduleRute->price_rute;
            $totalPrice = Bookings::calculateTotalWithAdminFee($request->payment_method, $basePrice);
            $adminFee = Bookings::calculateAdminFee($request->payment_method, $basePrice);

            // Log price calculation
            Log::info('Price Calculation:', [
                'base_price' => $basePrice,
                'admin_fee' => $adminFee,
                'total_price' => $totalPrice,
                'payment_method' => $request->payment_method
            ]);

            // Buat booking baru dengan user_id = 0 untuk guest
            $booking = Bookings::create([
                'user_id' => 0, // Set user_id = 0 untuk guest
                'schedule_id' => $scheduleRute->id,
                'name' => $request->booker['name'],
                'email' => $request->booker['email'],
                'phone_number' => $request->booker['phone'],
                'booking_date' => Carbon::now(),
                'payment_status' => 'UNPAID',
                'payment_method' => $request->payment_method,
                'final_price' => $totalPrice,
                'created_by_id' => 0, // Set created_by_id = 0 untuk guest
                'updated_by_id' => 0, // Set updated_by_id = 0 untuk guest
                'customer_type' => $request->customer_type
            ]);

            // Log booking data
            Log::info('Guest Booking Created:', [
                'booking' => $booking->toArray()
            ]);

            // Simpan data penumpang
            foreach ($request->passengers as $passenger) {
                // Ambil seat ID berdasarkan nomor kursi
                $seat = Seats::where('bus_id', $scheduleRute->schedule->bus_id)
                            ->where('seat_number', $passenger['seat_number'])
                            ->first();

                // Log seat data
                Log::info('Seat Data:', [
                    'bus_id' => $scheduleRute->schedule->bus_id,
                    'seat_number' => $passenger['seat_number'],
                    'seat' => $seat ? $seat->toArray() : null
                ]);

                if (!$seat) {
                    throw new \Exception('Kursi tidak ditemukan: ' . $passenger['seat_number']);
                }

                // Cek apakah kursi sudah dipesan
                $existingPassenger = Passenger::where('schedule_seat_id', $seat->id)
                    ->whereHas('booking', function($query) use ($scheduleRute) {
                        $query->where('schedule_id', $scheduleRute->id)
                              ->where('payment_status', '!=', 'CANCELLED');
                    })->first();

                // Log existing passenger
                Log::info('Existing Passenger:', [
                    'schedule_seat_id' => $seat->id,
                    'schedule_id' => $scheduleRute->id,
                    'passenger' => $existingPassenger ? $existingPassenger->toArray() : null
                ]);

                if ($existingPassenger) {
                    throw new \Exception('Kursi ' . $passenger['seat_number'] . ' sudah dipesan');
                }

                $passenger = Passenger::create([
                    'booking_id' => $booking->id,
                    'schedule_seat_id' => $seat->id,
                    'name' => $passenger['name'],
                    'gender' => $passenger['gender'],
                    'phone_number' => $passenger['phone'] ?? null,
                    'birth_date' => isset($passenger['birth_date']) ? $passenger['birth_date'] : null,
                    'description' => isset($passenger['description']) ? $passenger['description'] : null,
                    'created_by_id' => 0, // Set created_by_id = 0 untuk guest
                    'updated_by_id' => 0  // Set updated_by_id = 0 untuk guest
                ]);

                // Log passenger data
                Log::info('Guest Passenger Created:', [
                    'passenger' => $passenger->toArray()
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
                    'created_by_id' => 0, // Set created_by_id = 0 untuk guest
                    'updated_by_id' => 0  // Set updated_by_id = 0 untuk guest
                ]);

                // Log schedule seats data
                Log::info('Schedule Seats Created:', [
                    'schedule_id' => $scheduleRute->schedule_id,
                    'booking_id' => $booking->id,
                    'seat_id' => $seat->id,
                    'schedule_rute_id' => $scheduleRute->id,
                    'passengers_id' => $passenger->id
                ]);
            }

            // Konfigurasi Midtrans
            Config::$serverKey = config('midtrans.server_key');
            Config::$isProduction = config('midtrans.is_production');
            Config::$isSanitized = true;
            Config::$is3ds = true;

            // Siapkan item details untuk Midtrans
            $itemDetails = [];
            foreach ($request->passengers as $index => $passenger) {
                $itemDetails[] = [
                    'id' => 'SEAT-' . $passenger['seat_number'],
                    'price' => $scheduleRute->price_rute,
                    'quantity' => 1,
                    'name' => 'Tiket Bus - Kursi ' . $passenger['seat_number']
                ];
            }

            // Tambahkan biaya admin sebagai item terpisah jika ada
            if ($adminFee > 0) {
                $itemDetails[] = [
                    'id' => 'ADMIN-FEE',
                    'price' => $adminFee,
                    'quantity' => 1,
                    'name' => 'Biaya Admin ' . $request->payment_method
                ];
            }

            // Data transaksi Midtrans
            $transactionDetails = [
                'transaction_details' => [
                    'order_id' => 'BOOK-' . $booking->id . '-' . time(),
                    'gross_amount' => $totalPrice
                ],
                'customer_details' => [
                    'first_name' => $request->booker['name'],
                    'email' => $request->booker['email'],
                    'phone' => $request->booker['phone']
                ],
                'item_details' => $itemDetails,
                'enabled_payments' => [
                    'credit_card',
                    'cimb_clicks',
                    'bca_klikbca',
                    'bca_klikpay',
                    'bri_epay',
                    'echannel',
                    'permata_va',
                    'bca_va',
                    'bni_va',
                    'bri_va',
                    'cimb_va',
                    'other_va',
                    'gopay',
                    'indomaret',
                    'danamon_online',
                    'akulaku',
                    'shopeepay',
                    'kredivo',
                    'uob_ezpay',
                    'alfamart'
                ],
                'expiry' => [
                    'unit' => 'minute',
                    'duration' => 8
                ],
                'callbacks' => [
                    'finish' => url('/api/midtrans/finish'),
                    'error' => url('/api/midtrans/error'),
                    'notification' => url('/api/midtrans/notification')
                ]
            ];

            // Log transaction details
            Log::info('Midtrans Transaction Details:', [
                'transaction' => $transactionDetails
            ]);

            // Membuat Snap Token
            $snapToken = Snap::getSnapToken($transactionDetails);

            // Log snap token
            Log::info('Midtrans Snap Token:', [
                'token' => $snapToken
            ]);

            // Update booking dengan snap token
            $booking->update([
                'payment_id' => $transactionDetails['transaction_details']['order_id'],
                'redirect_url' => 'https://app.' . (config('midtrans.is_production') ? '' : 'sandbox.') . 'midtrans.com/snap/v2/vtweb/' . $snapToken
            ]);

            // Log booking update
            Log::info('Guest Booking Updated:', [
                'booking' => $booking->fresh()->toArray()
            ]);

            // Format pesan WhatsApp dengan tambahan informasi biaya admin
            $message = "🚌 *Konfirmasi Booking Bus*\n\n"
                    . "Hai {$request->booker['name']},\n"
                    . "Terima kasih telah melakukan pemesanan tiket bus. Berikut detail pesanan Anda:\n\n"
                    . "🎫 *Detail Booking*\n"
                    . "Booking ID: {$booking->payment_id}\n"
                    . "Subtotal: Rp " . number_format($basePrice, 0, ',', '.') . "\n"
                    . ($adminFee > 0 ? "Biaya Admin: Rp " . number_format($adminFee, 0, ',', '.') . "\n" : "")
                    . "Total Pembayaran: Rp " . number_format($totalPrice, 0, ',', '.') . "\n"
                    . "Metode Pembayaran: " . $request->payment_method . "\n"
                    . "Jumlah Kursi: " . count($request->passengers) . "\n\n"
                    . "💳 *Instruksi Pembayaran*\n"
                    . "1. Klik link pembayaran di bawah ini\n"
                    . "2. Pilih metode pembayaran yang tersedia\n"
                    . "3. Lakukan pembayaran sesuai instruksi\n\n"
                    . "🔗 Link Pembayaran:\n"
                    . "https://app." . (config('midtrans.is_production') ? '' : 'sandbox.') . "midtrans.com/snap/v2/vtweb/" . $snapToken . "\n\n"
                    . "⚠️ *PENTING*\n"
                    . "• Pembayaran akan kadaluarsa dalam 24 jam\n"
                    . "• Kursi Anda belum terkonfirmasi sebelum pembayaran selesai\n\n"
                    . "Jika ada pertanyaan, silakan hubungi kami di 085600121760\n\n"
                    . "Terima kasih! 🙏";

            // Log WhatsApp message
            Log::info('WhatsApp Message:', [
                'phone' => $booking->phone_number,
                'message' => $message
            ]);

            // Kirim pesan WhatsApp
            $this->sendWhatsAppMessage($booking->phone_number, $message);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Booking berhasil dibuat',
                'data' => [
                    'booking' => $booking->load('passengers'),
                    'payment' => [
                        'token' => $snapToken,
                        'redirect_url' => 'https://app.' . (config('midtrans.is_production') ? '' : 'sandbox.') . 'midtrans.com/snap/v2/vtweb/' . $snapToken
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Guest Booking Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'status' => false,
                'message' => 'Gagal membuat booking',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
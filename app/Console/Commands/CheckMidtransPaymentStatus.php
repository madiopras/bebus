<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Bookings;
use App\Models\MidtransLog;
use Illuminate\Support\Facades\Log;
use Midtrans\Config;
use Midtrans\Transaction;
use Carbon\Carbon;

class CheckMidtransPaymentStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'midtrans:check-payment-status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cek status pembayaran di Midtrans untuk booking yang belum dibayar';

    public function __construct()
    {
        parent::__construct();
        
        // Konfigurasi Midtrans
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = config('midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    private function sendWhatsAppMessage($to, $message)
    {
        try {
            Log::info('Attempting to send WhatsApp message', [
                'to' => $to,
                'message_length' => strlen($message)
            ]);
            // Implementasi pengiriman WhatsApp
        } catch (\Exception $e) {
            Log::error('WhatsApp Message Error:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function sendSuccessNotification($booking)
    {
        $message = "✅ *Pembayaran Berhasil*\n\n"
                . "Hai {$booking->name},\n"
                . "Pembayaran tiket bus Anda telah berhasil dikonfirmasi.\n\n"
                . "🎫 *Detail Booking*\n"
                . "Booking ID: BOOK-{$booking->id}\n"
                . "Total Pembayaran: Rp " . number_format($booking->final_price, 0, ',', '.') . "\n\n"
                . "Terima kasih telah menggunakan layanan kami! 🙏";
        $this->sendWhatsAppMessage($booking->phone_number, $message);
    }

    private function sendFailureNotification($booking)
    {
        $message = "❌ *Pembayaran Gagal*\n\n"
                . "Hai {$booking->name},\n"
                . "Mohon maaf, pembayaran tiket bus Anda tidak berhasil.\n\n"
                . "🎫 *Detail Booking*\n"
                . "Booking ID: BOOK-{$booking->id}\n"
                . "Total: Rp " . number_format($booking->final_price, 0, ',', '.') . "\n\n"
                . "Silakan lakukan pemesanan ulang. Terima kasih! 🙏";
        $this->sendWhatsAppMessage($booking->phone_number, $message);
    }

    private function cancelBooking($booking, $reason)
    {
        $booking->payment_status = 'CANCELLED';
        $booking->save();

        \App\Models\ScheduleSeats::where('booking_Id', $booking->id)
            ->update([
                'is_available' => 1,
                'description' => 'Kursi tersedia kembali karena ' . $reason
            ]);

        $this->sendFailureNotification($booking);

        MidtransLog::create([
            'order_id' => $booking->payment_id,
            'booking_id' => $booking->id,
            'transaction_status' => 'cancelled',
            'payment_status' => 'CANCELLED',
            'midtrans_response' => ['status_message' => $reason]
        ]);

        $this->info("Booking ID {$booking->id} diupdate ke CANCELLED karena {$reason}");
        Log::info("Booking ID {$booking->id} diupdate ke CANCELLED karena {$reason}");
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (!config('midtrans.enable_payment_check', false)) {
            $this->info('Pengecekan pembayaran Midtrans dinonaktifkan');
            return;
        }

        try {
            $this->info('Memulai pengecekan status pembayaran Midtrans');
            Log::info('Memulai pengecekan status pembayaran Midtrans');
            
            // Ambil semua booking yang masih UNPAID
            $unpaidBookings = Bookings::where('payment_status', 'UNPAID')
                ->whereNotNull('payment_id')
                ->get();

            $this->info("Ditemukan {$unpaidBookings->count()} booking yang perlu dicek");
            Log::info("Ditemukan {$unpaidBookings->count()} booking yang perlu dicek");

            foreach ($unpaidBookings as $booking) {
                try {
                    $this->info("Mengecek booking ID: {$booking->id} dengan payment ID: {$booking->payment_id}");
                    Log::info("Mengecek booking ID: {$booking->id} dengan payment ID: {$booking->payment_id}");

                    // Cek apakah booking sudah lebih dari 8 menit
                    $bookingTime = Carbon::parse($booking->created_at);
                    $now = Carbon::now();
                    $minutesDiff = $now->diffInMinutes($bookingTime);

                    if ($minutesDiff > 8) {
                        $this->cancelBooking($booking, 'waktu pembayaran telah habis (lebih dari 8 menit)');
                        continue;
                    }

                    // Validasi status transaksi ke Midtrans
                    try {
                        $midtransStatus = Transaction::status($booking->payment_id);
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

                        // Simpan log Midtrans
                        MidtransLog::create([
                            'order_id' => $booking->payment_id,
                            'booking_id' => $booking->id,
                            'transaction_status' => $midtransStatusArray['transaction_status'],
                            'payment_status' => $paymentStatus,
                            'midtrans_response' => $midtransStatusArray
                        ]);

                        // Update status booking
                        $booking->payment_status = $paymentStatus;
                        $booking->save();

                        // Update kursi dan kirim notifikasi
                        if ($paymentStatus == 'PAID') {
                            \App\Models\ScheduleSeats::where('booking_Id', $booking->id)
                                ->update([
                                    'is_available' => 0,
                                    'description' => 'Kursi telah dibayar'
                                ]);
                            $this->sendSuccessNotification($booking);
                        } 
                        elseif (in_array($paymentStatus, ['CANCELLED', 'EXPIRED'])) {
                            \App\Models\ScheduleSeats::where('booking_Id', $booking->id)
                                ->update([
                                    'is_available' => 1,
                                    'description' => 'Kursi tersedia kembali karena pembayaran dibatalkan'
                                ]);
                            $this->sendFailureNotification($booking);
                        }

                        $this->info("Booking ID {$booking->id} diupdate ke {$paymentStatus}");
                        Log::info("Booking ID {$booking->id} diupdate ke {$paymentStatus}");

                    } catch (\Exception $e) {
                        // Jika transaksi tidak ditemukan (404) atau error lainnya, tetap cancel jika lebih dari 8 menit
                        if ($minutesDiff > 8) {
                            $this->cancelBooking($booking, 'waktu pembayaran telah habis (lebih dari 8 menit)');
                        }
                        // Jika belum 8 menit dan transaksi tidak ditemukan
                        else if (strpos($e->getMessage(), "404") !== false || 
                            strpos($e->getMessage(), "Transaction doesn't exist") !== false) {
                            
                            $this->cancelBooking($booking, 'transaksi tidak ditemukan');
                        }
                        else {
                            Log::error('Midtrans Status Check Error:', [
                                'message' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                            throw new \Exception('Gagal memvalidasi status pembayaran: ' . $e->getMessage());
                        }
                    }

                } catch (\Exception $e) {
                    $this->error("Error saat mengecek booking ID {$booking->id}: " . $e->getMessage());
                    Log::error("Error saat mengecek booking ID {$booking->id}: " . $e->getMessage());
                    continue;
                }
            }

            $this->info('Pengecekan status pembayaran selesai');
            Log::info('Pengecekan status pembayaran selesai');

        } catch (\Exception $e) {
            $this->error('Error pada job pengecekan pembayaran: ' . $e->getMessage());
            Log::error('Error pada job pengecekan pembayaran: ' . $e->getMessage());
            throw $e;
        }
    }
}

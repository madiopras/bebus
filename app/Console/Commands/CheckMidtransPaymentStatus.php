<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Bookings;
use App\Models\MidtransLog;
use Midtrans\Config;
use Midtrans\Transaction;
use Illuminate\Support\Facades\Log;
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
    protected $description = 'Mengecek status pembayaran di Midtrans untuk booking yang belum dibayar';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Konfigurasi Midtrans
            Config::$serverKey = config('midtrans.server_key');
            Config::$isProduction = config('midtrans.is_production');

            // Ambil semua booking yang belum dibayar dan belum expired
            $unpaidBookings = Bookings::where('payment_status', 'UNPAID')
                ->where('created_at', '>=', Carbon::now()->subMinutes(8))
                ->get();

            $this->info("Mengecek " . $unpaidBookings->count() . " booking yang belum dibayar");

            foreach ($unpaidBookings as $booking) {
                try {
                    if (!$booking->payment_id) {
                        continue;
                    }

                    // Cek status di Midtrans
                    $midtransStatus = Transaction::status($booking->payment_id);
                    $statusArray = (array) $midtransStatus;

                    // Tentukan payment status
                    $paymentStatus = 'PENDING';
                    switch ($statusArray['transaction_status'] ?? '') {
                        case 'capture':
                            if (($statusArray['fraud_status'] ?? '') == 'challenge') {
                                $paymentStatus = 'CHALLENGE';
                            } else if (($statusArray['fraud_status'] ?? '') == 'accept') {
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
                        case 'cancel':
                        case 'expire':
                        case 'failure':
                            $paymentStatus = 'CANCELLED';
                            break;
                        case 'refund':
                            $paymentStatus = 'REFUNDED';
                            break;
                    }

                    // Update status booking jika berbeda
                    if ($booking->payment_status != $paymentStatus) {
                        $booking->payment_status = $paymentStatus;
                        $booking->save();

                        // Simpan log
                        MidtransLog::create([
                            'order_id' => $booking->payment_id,
                            'booking_id' => $booking->id,
                            'transaction_status' => $statusArray['transaction_status'] ?? '',
                            'payment_status' => $paymentStatus,
                            'midtrans_response' => $statusArray
                        ]);

                        // Update status kursi jika pembayaran berhasil atau dibatalkan
                        if ($paymentStatus == 'PAID') {
                            \App\Models\ScheduleSeats::where('booking_id', $booking->id)
                                ->update([
                                    'is_available' => false,
                                    'description' => 'Kursi telah dibayar'
                                ]);

                            // Kirim notifikasi WhatsApp
                            $message = "✅ *Pembayaran Berhasil*\n\n"
                                . "Hai {$booking->name},\n"
                                . "Pembayaran tiket bus Anda telah berhasil dikonfirmasi.\n\n"
                                . "🎫 *Detail Booking*\n"
                                . "Booking ID: BOOK-{$booking->id}\n"
                                . "Total Pembayaran: Rp " . number_format($booking->final_price, 0, ',', '.') . "\n\n"
                                . "Terima kasih telah menggunakan layanan kami! 🙏";

                            // Panggil service WhatsApp untuk mengirim pesan
                            app(\App\Services\WhatsAppService::class)->sendMessage($booking->phone_number, $message);
                        } 
                        elseif (in_array($paymentStatus, ['CANCELLED', 'EXPIRED'])) {
                            \App\Models\ScheduleSeats::where('booking_id', $booking->id)
                                ->update([
                                    'is_available' => true,
                                    'description' => 'Kursi tersedia kembali karena pembayaran dibatalkan'
                                ]);

                            // Kirim notifikasi WhatsApp
                            $message = "❌ *Pembayaran Gagal*\n\n"
                                . "Hai {$booking->name},\n"
                                . "Mohon maaf, pembayaran tiket bus Anda tidak berhasil.\n\n"
                                . "🎫 *Detail Booking*\n"
                                . "Booking ID: BOOK-{$booking->id}\n"
                                . "Total: Rp " . number_format($booking->final_price, 0, ',', '.') . "\n\n"
                                . "Silakan lakukan pemesanan ulang. Terima kasih! 🙏";

                            // Panggil service WhatsApp untuk mengirim pesan
                            app(\App\Services\WhatsAppService::class)->sendMessage($booking->phone_number, $message);
                        }
                    }

                    $this->info("Berhasil mengecek booking ID: " . $booking->id);
                } catch (\Exception $e) {
                    $this->error("Error saat mengecek booking ID " . $booking->id . ": " . $e->getMessage());
                    Log::error('Midtrans Check Payment Error:', [
                        'booking_id' => $booking->id,
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    continue;
                }
            }

            $this->info("Selesai mengecek status pembayaran");
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
            Log::error('Midtrans Check Payment Command Error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}

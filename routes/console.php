<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule; 

Artisan::command('schedule:list', function (Schedule $schedule) {
    $schedule->command('check:midtrans-payment-status')->cron('*/8 * * * *'); // Setiap 8 Menit
    // Ganti everyMinute() sesuai kebutuhan Anda everyFiveMinutes(), hourly(), daily(), everyMinute()
});

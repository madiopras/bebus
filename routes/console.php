<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Console\Scheduling\Schedule;

Artisan::command('schedule:list', function (Schedule $schedule) {
    //$schedule->command('midtrans:check-payment-status')
            //->cron('*/8 * * * *')
            //->withoutOverlapping(10);
            //->appendOutputTo(storage_path('logs/midtrans-payment-check.log'));
    //$schedule->command('check:midtrans-payment-status')->hourly(); // Setiap 8 Menit
    // Ganti everyMinute() sesuai kebutuhan Anda everyFiveMinutes(), hourly(), daily(), everyMinute()
});



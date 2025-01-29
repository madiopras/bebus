<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Console\Scheduling\Schedule;

class MidtransSchedulerServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
      $this->app->booted(function () {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('midtrans:check-payment-status')
                ->everyMinute()
                ->withoutOverlapping(5)
                ->appendOutputTo(storage_path('logs/midtrans-payment-check.log'));
        });

        //$schedule->command('midtrans:check-payment-status')
            //->cron('*/8 * * * *')
            //->withoutOverlapping(10);
            //->appendOutputTo(storage_path('logs/midtrans-payment-check.log'));
    //$schedule->command('check:midtrans-payment-status')->hourly(); // Setiap 8 Menit
    // Ganti everyMinute() sesuai kebutuhan Anda everyFiveMinutes(), hourly(), daily(), everyMinute()

    }
}

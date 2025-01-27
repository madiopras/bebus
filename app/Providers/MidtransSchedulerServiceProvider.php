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
                ->everyThirtyMinutes()
                ->withoutOverlapping(5)
                ->appendOutputTo(storage_path('logs/midtrans-payment-check.log'));
        });
    }
}

<?php

namespace App\Providers;

use App\Services\Booking\SlotGenerator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SlotGenerator::class, function () {
            return new SlotGenerator(
                leadTimeMinutes: (int) config('nobat.booking.lead_time_minutes', 30),
                maxAdvanceDays: (int) config('nobat.booking.max_advance_days', 14),
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}

<?php

namespace App\Providers;

use App\Services\Booking\SlotGenerator;
use App\Services\Sms\SmsManager;
use Illuminate\Support\Facades\URL;
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

        $this->app->singleton(SmsManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // The demo/production deployment always sits behind a TLS terminator;
        // never emit http:// links from it (mixed content = blank page).
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}

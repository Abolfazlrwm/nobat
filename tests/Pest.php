<?php

use App\Models\WorkingHour;
use App\Services\Booking\SlotGenerator;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\HasBookingWorld;
use Tests\TestCase;

uses(TestCase::class, HasBookingWorld::class, RefreshDatabase::class)->in('Feature');

/**
 * A fresh generator with default config values.
 */
function generator(): SlotGenerator
{
    return new SlotGenerator(leadTimeMinutes: 30, maxAdvanceDays: 14);
}

/**
 * A date on or after today whose weekday index is `$weekday`.
 */
function nextDateWithWeekday(int $weekday): CarbonImmutable
{
    $date = CarbonImmutable::today()->addDay();

    while (WorkingHour::weekdayFromCarbon($date) !== $weekday) {
        $date = $date->addDay();
    }

    return $date;
}

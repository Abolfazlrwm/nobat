<?php

namespace App\Services\Booking;

use Carbon\CarbonImmutable;

/**
 * Immutable value object representing one bookable time slot.
 *
 * A single slot may be offered by several staff members at once;
 * `staffIds` holds everyone who is still free in that window.
 */
final class Slot
{
    /**
     * @param  list<int>  $staffIds
     */
    public function __construct(
        public readonly CarbonImmutable $startsAt,
        public readonly CarbonImmutable $endsAt,
        public readonly array $staffIds,
    ) {}

    public function isAvailable(): bool
    {
        return $this->staffIds !== [];
    }
}

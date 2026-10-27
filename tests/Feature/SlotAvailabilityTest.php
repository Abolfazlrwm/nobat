<?php

use App\Models\Appointment;
use App\Models\WorkingHour;
use App\Services\Booking\SlotGenerator;
use Carbon\CarbonImmutable;

covers(SlotGenerator::class);

it('generates one slot per hour inside a four-hour window', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $this->addStaff($business);
    $date = $this->nextOpenDate();

    $slots = generator()->generate($business, $service, null, $date);

    // Window is 09:00-13:00 with a 60 minute service => 4 slots.
    expect($slots)->toHaveCount(4)
        ->and($slots->first()->startsAt->format('H:i'))->toBe('09:00')
        ->and($slots->last()->startsAt->format('H:i'))->toBe('12:00');
});

it('returns no slots on the weekly day off', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $friday = nextDateWithWeekday(WorkingHour::FRIDAY);

    $slots = generator()->generate($business, $service, null, $friday);

    expect($slots)->toBeEmpty();
});

it('removes slots that overlap an existing appointment', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $date = $this->nextOpenDate();

    $this->createAppointment($staff, $service, $this->slotAt($date, '10:00'));

    $slots = generator()->generate($business, $service, $staff, $date);

    expect($slots->pluck('startsAt')->map->format('H:i')->all())
        ->toBe(['09:00', '11:00', '12:00']);
});

it('keeps a slot free for other staff while one member is busy', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $busy = $this->addStaff($business, 'همکار اول');
    $free = $this->addStaff($business, 'همکار دوم');
    $date = $this->nextOpenDate();

    $this->createAppointment($busy, $service, $this->slotAt($date, '10:00'));

    $slots = generator()->generate($business, $service, null, $date);
    $tenOclock = $slots->first(fn ($slot) => $slot->startsAt->format('H:i') === '10:00');

    expect($tenOclock->staffIds)->toBe([$free->id])
        ->and($slots->first()->staffIds)->toContain($busy->id);
});

it('drops past and too-soon slots when booking for today', function () {
    // Freeze the clock on a Monday mid-morning so the assertion is deterministic.
    CarbonImmutable::setTestNow(CarbonImmutable::parse('next monday')->setTime(10, 30));

    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $this->addStaff($business);

    $slots = generator()->generate($business, $service, null, CarbonImmutable::today());

    $cutoff = CarbonImmutable::now()->addMinutes(30);

    expect($slots)->not->toBeEmpty();

    foreach ($slots as $slot) {
        expect($slot->startsAt->greaterThanOrEqualTo($cutoff))->toBeTrue();
    }

    CarbonImmutable::setTestNow();
});

it('returns nothing beyond the booking horizon', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);

    $slots = generator()->generate($business, $service, null, CarbonImmutable::today()->addDays(15));

    expect($slots)->toBeEmpty();
});

it('prefers staff specific hours over business defaults', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $date = $this->nextOpenDate();

    // This member only works the second half of the day.
    $business->workingHours()->create([
        'staff_id' => $staff->id,
        'weekday' => WorkingHour::weekdayFromCarbon($date),
        'starts_at' => '17:00',
        'ends_at' => '20:00',
    ]);

    $slots = generator()->generate($business, $service, $staff, $date);

    expect($slots->first()->startsAt->format('H:i'))->toBe('17:00')
        ->and($slots)->toHaveCount(3);
});

it('ignores cancelled appointments when freeing slots', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $date = $this->nextOpenDate();

    $this->createAppointment($staff, $service, $this->slotAt($date, '10:00'), Appointment::STATUS_CANCELLED);

    $slots = generator()->generate($business, $service, $staff, $date);

    expect($slots)->toHaveCount(4);
});

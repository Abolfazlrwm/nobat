<?php

use App\Models\Appointment;
use App\Models\DiscountCode;
use Illuminate\Support\Facades\Queue;

it('creates a standing series — the anchor slot plus weeks beyond the advance window', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $startsAt = $this->slotAt($this->nextOpenDate(), '10:00');

    $payload = $this->validBookingPayload($business, $service, $startsAt, $staff->id)
        + ['repeat_weeks' => 4];

    $this->post(route('bookings.store', $business->slug), $payload)->assertRedirect();

    $series = Appointment::query()->whereNotNull('series_token')->orderBy('starts_at')->get();

    expect($series)->toHaveCount(4)
        ->and($series->pluck('series_token')->unique())->toHaveCount(1)
        ->and($series[1]->starts_at->equalTo($series[0]->starts_at->addWeeks(1)))->toBeTrue()
        ->and($series[3]->starts_at->equalTo($series[0]->starts_at->addWeeks(3)))->toBeTrue();

    // The window invariant still guards ordinary bookings: the same
    // far-away slot, requested without a series, is rejected.
    // Model datetime casts hydrate as Support\Carbon — hand the helper
    // the immutable it insists on.
    $plain = $this->validBookingPayload($business, $service, $series[3]->starts_at->toImmutable(), $staff->id);

    $this->from(route('home'))
        ->post(route('bookings.store', $business->slug), $plain)
        ->assertRedirect(route('home'))
        ->assertSessionHasErrors('starts_at');

    expect(Appointment::query()->count())->toBe(4);
});

it('rolls the whole series back when a later week collides', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $startsAt = $this->slotAt($this->nextOpenDate(), '10:00');

    // Week three is already taken by someone else.
    $this->createAppointment($staff, $service, $startsAt->addWeeks(2));

    $payload = $this->validBookingPayload($business, $service, $startsAt, $staff->id)
        + ['repeat_weeks' => 4];

    $this->from(route('home'))
        ->post(route('bookings.store', $business->slug), $payload)
        ->assertRedirect(route('home'))
        ->assertSessionHasErrors('starts_at');

    // Weeks one and two rolled back with week three; only the blocker
    // remains. The booking SMS jobs ride on afterCommit, so nothing that
    // was queued along the way ever fires for a rolled-back series.
    expect(Appointment::query()->count())->toBe(1)
        ->and(Appointment::query()->whereNotNull('series_token')->count())->toBe(0);
});

it('refuses absurd series lengths', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $startsAt = $this->slotAt($this->nextOpenDate(), '10:00');

    $this->post(route('bookings.store', $business->slug),
        $this->validBookingPayload($business, $service, $startsAt, $staff->id) + ['repeat_weeks' => 99]
    )->assertSessionHasErrors('repeat_weeks');

    $this->post(route('bookings.store', $business->slug),
        $this->validBookingPayload($business, $service, $startsAt, $staff->id) + ['repeat_weeks' => 0]
    )->assertSessionHasErrors('repeat_weeks');

    expect(Appointment::query()->count())->toBe(0);
});

it('applies the discount to every occurrence of the series', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $startsAt = $this->slotAt($this->nextOpenDate(), '10:00');

    /** @var DiscountCode $code */
    $code = $business->discountCodes()->create([
        'code' => 'WELCOME'.Str::random(4),
        'type' => DiscountCode::TYPE_PERCENT,
        'value' => 10,
        'is_active' => true,
    ]);

    $payload = $this->validBookingPayload($business, $service, $startsAt, $staff->id)
        + ['repeat_weeks' => 3, 'discount_code' => $code->code];

    $this->post(route('bookings.store', $business->slug), $payload)->assertRedirect();

    $series = Appointment::query()->whereNotNull('series_token')->get();

    expect($series)->toHaveCount(3)
        ->and($series->every(fn (Appointment $appointment) => $appointment->discount_amount === 50_000))->toBeTrue();
});

it('lists the series siblings on the tracking page', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $startsAt = $this->slotAt($this->nextOpenDate(), '10:00');

    $payload = $this->validBookingPayload($business, $service, $startsAt, $staff->id)
        + ['repeat_weeks' => 3];

    $this->post(route('bookings.store', $business->slug), $payload)->assertRedirect();

    $series = Appointment::query()->whereNotNull('series_token')->orderBy('starts_at')->get();

    $this->get(route('bookings.show', $series[0]->code))
        ->assertOk()
        ->assertInertia(fn (Inertia\Testing\AssertableInertia $page) => $page
            ->has('series', 2)
            ->where('series.0.code', $series[1]->code)
            ->where('series.1.code', $series[2]->code)
            ->where('series.0.status', $series[1]->status));

    // A one-off booking carries no series payload.
    $oneOff = $this->createAppointment($staff, $service, $startsAt->addHours(3));

    $this->get(route('bookings.show', $oneOff->code))
        ->assertInertia(fn (Inertia\Testing\AssertableInertia $page) => $page
            ->has('series', 0));
});

it('keeps the series independent — cancelling one week leaves the rest alive', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $startsAt = $this->slotAt($this->nextOpenDate(), '10:00');

    $payload = $this->validBookingPayload($business, $service, $startsAt, $staff->id)
        + ['repeat_weeks' => 2];

    $this->post(route('bookings.store', $business->slug), $payload)->assertRedirect();

    $series = Appointment::query()->whereNotNull('series_token')->orderBy('starts_at')->get();

    $this->post(route('bookings.guest_cancel', $series[1]->code), [
        'customer_phone' => '09121234567',
    ])->assertRedirect();

    expect($series[0]->refresh()->status)->toBe(Appointment::STATUS_PENDING)
        ->and($series[1]->refresh()->status)->toBe(Appointment::STATUS_CANCELLED)
        ->and($series[0]->seriesSiblings())->toHaveCount(1);
});

<?php

use App\Jobs\SendRescheduleSms;
use App\Jobs\SendWaitlistPromoSms;
use App\Models\Appointment;
use App\Models\WaitlistEntry;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia;

function waitingEntryForReschedule($business, $service, string $date, array $overrides = []): WaitlistEntry
{
    return $business->waitlistEntries()->create($overrides + [
        'service_id' => $service->id,
        'desired_date' => $date,
        'customer_name' => 'مریم انتظاری',
        'customer_phone' => '09123334455',
        'status' => WaitlistEntry::STATUS_WAITING,
    ]);
}

it('moves a booking to another free slot when the phone proves ownership', function () {
    Queue::fake();

    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $day = $this->nextOpenDate();
    $appointment = $this->createAppointment($staff, $this->serviceOf($business), $day->setTimeFromTimeString('10:00'), Appointment::STATUS_CONFIRMED);

    $newStartsAt = $day->setTimeFromTimeString('11:00');

    $this->patch(route('bookings.reschedule', $appointment->code), [
        'customer_phone' => '09120000000',
        'starts_at' => $newStartsAt->format('Y-m-d\TH:i:s'),
    ])
        ->assertRedirect()
        ->assertSessionHas('success', __('bookings.rescheduled'));

    expect($appointment->refresh()->starts_at->equalTo($newStartsAt))->toBeTrue()
        ->and($appointment->ends_at->equalTo($newStartsAt->addMinutes(60)))->toBeTrue()
        ->and($appointment->status)->toBe(Appointment::STATUS_CONFIRMED)
        ->and($appointment->staff_id)->toBe($staff->id);

    Queue::assertPushed(SendRescheduleSms::class, fn (SendRescheduleSms $job) => $job->appointment->is($appointment)
        && $job->oldStartsAt->format('H:i') === '10:00'
        && $job->locale === app()->getLocale());
});

it('accepts persian digits and formatting in the phone proof', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $day = $this->nextOpenDate();
    $appointment = $this->createAppointment($staff, $this->serviceOf($business), $day->setTimeFromTimeString('10:00'), Appointment::STATUS_CONFIRMED);

    $this->patch(route('bookings.reschedule', $appointment->code), [
        'customer_phone' => '۰۹۱۲ ۰۰۰ ۰۰۰۰',
        'starts_at' => $day->setTimeFromTimeString('11:00')->format('Y-m-d\TH:i:s'),
    ])->assertRedirect();

    expect($appointment->refresh()->starts_at->format('H:i'))->toBe('11:00');
});

it('refuses a reschedule when the phone does not match', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $day = $this->nextOpenDate();
    $appointment = $this->createAppointment($staff, $this->serviceOf($business), $day->setTimeFromTimeString('10:00'), Appointment::STATUS_CONFIRMED);

    $this->patch(route('bookings.reschedule', $appointment->code), [
        'customer_phone' => '09350000000',
        'starts_at' => $day->setTimeFromTimeString('11:00')->format('Y-m-d\TH:i:s'),
    ])
        ->assertRedirect()
        ->assertSessionHasErrors('customer_phone');

    expect($appointment->refresh()->starts_at->format('H:i'))->toBe('10:00');
});

it('refuses to move a cancelled or completed booking', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $day = $this->nextOpenDate();

    foreach ([Appointment::STATUS_CANCELLED, Appointment::STATUS_COMPLETED] as $status) {
        $appointment = $this->createAppointment($staff, $this->serviceOf($business), $day->setTimeFromTimeString('10:00'), $status);

        $this->patch(route('bookings.reschedule', $appointment->code), [
            'customer_phone' => '09120000000',
            'starts_at' => $day->setTimeFromTimeString('11:00')->format('Y-m-d\TH:i:s'),
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('starts_at');

        expect($appointment->refresh()->starts_at->format('H:i'))->toBe('10:00');
    }
});

it('refuses to move a booking whose time has already started', function () {
    // Freeze the clock mid-morning so "today at 08:00" is always already
    // started — a CI runner that lands before 08:00 made this flaky.
    CarbonImmutable::setTestNow(CarbonImmutable::today()->setTimeFromTimeString('10:00'));

    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $appointment = $this->createAppointment(
        $staff,
        $this->serviceOf($business),
        CarbonImmutable::today()->setTimeFromTimeString('08:00'),
        Appointment::STATUS_CONFIRMED,
    );

    $this->patch(route('bookings.reschedule', $appointment->code), [
        'customer_phone' => '09120000000',
        'starts_at' => $this->nextOpenDate()->setTimeFromTimeString('11:00')->format('Y-m-d\TH:i:s'),
    ])
        ->assertRedirect()
        ->assertSessionHasErrors('starts_at');

    expect($appointment->refresh()->starts_at->format('H:i'))->toBe('08:00');

    CarbonImmutable::setTestNow();
});

it('refuses to move into another booking\'s window', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $day = $this->nextOpenDate();
    $appointment = $this->createAppointment($staff, $this->serviceOf($business), $day->setTimeFromTimeString('10:00'), Appointment::STATUS_CONFIRMED);
    $other = $this->createAppointment($staff, $this->serviceOf($business), $day->setTimeFromTimeString('11:00'), Appointment::STATUS_CONFIRMED);

    $this->patch(route('bookings.reschedule', $appointment->code), [
        'customer_phone' => '09120000000',
        'starts_at' => $day->setTimeFromTimeString('11:00')->format('Y-m-d\TH:i:s'),
    ])
        ->assertRedirect()
        ->assertSessionHasErrors('starts_at');

    expect($appointment->refresh()->starts_at->format('H:i'))->toBe('10:00')
        ->and($other->refresh()->starts_at->format('H:i'))->toBe('11:00');
});

it('tells an off-grid time apart from a taken one', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $day = $this->nextOpenDate();
    $appointment = $this->createAppointment($staff, $this->serviceOf($business), $day->setTimeFromTimeString('10:00'), Appointment::STATUS_CONFIRMED);

    // 10:30 is inside the working window but the hourly grid starts at 10:00.
    $this->patch(route('bookings.reschedule', $appointment->code), [
        'customer_phone' => '09120000000',
        'starts_at' => $day->setTimeFromTimeString('10:30')->format('Y-m-d\TH:i:s'),
    ])
        ->assertRedirect()
        ->assertSessionHasErrors(['starts_at' => __('bookings.slot_invalid')]);

    // Beyond the booking horizon is also structurally invalid, not "taken".
    $this->patch(route('bookings.reschedule', $appointment->code), [
        'customer_phone' => '09120000000',
        'starts_at' => $day->addDays(30)->setTimeFromTimeString('11:00')->format('Y-m-d\TH:i:s'),
    ])
        ->assertRedirect()
        ->assertSessionHasErrors(['starts_at' => __('bookings.slot_invalid')]);

    expect($appointment->refresh()->starts_at->format('H:i'))->toBe('10:00');
});

it('announces the vacated old slot to the waiting list', function () {
    Queue::fake();

    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $day = $this->nextOpenDate();
    $appointment = $this->createAppointment($staff, $this->serviceOf($business), $day->setTimeFromTimeString('10:00'), Appointment::STATUS_CONFIRMED);
    $entry = waitingEntryForReschedule($business, $this->serviceOf($business), $day->format('Y-m-d'));

    $this->patch(route('bookings.reschedule', $appointment->code), [
        'customer_phone' => '09120000000',
        'starts_at' => $day->setTimeFromTimeString('11:00')->format('Y-m-d\TH:i:s'),
    ])->assertRedirect();

    Queue::assertPushed(SendWaitlistPromoSms::class, 1);
    expect($entry->refresh()->status)->toBe(WaitlistEntry::STATUS_NOTIFIED)
        ->and($entry->notified_at)->not->toBeNull();
});

it('returns 404 for an unknown tracking code', function () {
    $this->patch(route('bookings.reschedule', 'not-a-code'), [
        'customer_phone' => '09120000000',
        'starts_at' => $this->nextOpenDate()->setTimeFromTimeString('11:00')->format('Y-m-d\TH:i:s'),
    ])->assertNotFound();
});

it('sends the reschedule sms in the language the booking was made in', function () {
    Queue::fake();

    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $day = $this->nextOpenDate();
    $appointment = $this->createAppointment($staff, $this->serviceOf($business), $day->setTimeFromTimeString('10:00'), Appointment::STATUS_CONFIRMED);
    $appointment->forceFill(['locale' => 'ar'])->save();

    $this->patch(route('bookings.reschedule', $appointment->code), [
        'customer_phone' => '09120000000',
        'starts_at' => $day->setTimeFromTimeString('11:00')->format('Y-m-d\TH:i:s'),
    ])->assertRedirect();

    Queue::assertPushed(SendRescheduleSms::class, fn (SendRescheduleSms $job) => $job->locale === 'ar');
});

it('shows the reschedule picker its own service and staff ids', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $appointment = $this->createAppointment($staff, $this->serviceOf($business), $this->nextOpenDate()->setTimeFromTimeString('10:00'), Appointment::STATUS_CONFIRMED);

    $this->get(route('bookings.show', $appointment->code))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/BookingConfirmed')
            ->where('appointment.service_id', $appointment->service_id)
            ->where('appointment.staff_id', $staff->id));
});

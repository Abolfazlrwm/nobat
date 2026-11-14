<?php

use App\Models\Appointment;
use App\Models\User;
use App\Models\WaitlistEntry;
use App\Notifications\AppointmentStatusChanged;
use Illuminate\Support\Facades\Queue;

it('shares the info flash with the inertia payload, not just the session', function () {
    // The waitlist "n customers were notified" message is flashed as `info`.
    // Without sharing it through HandleInertiaRequests, the manager never
    // sees the toast — even though the session flag is set.
    Queue::fake();

    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $startsAt = $this->nextOpenDate()->setTimeFromTimeString('10:00');
    $appointment = $this->createAppointment($this->addStaff($business), $service, $startsAt, Appointment::STATUS_CONFIRMED);

    $business->waitlistEntries()->create([
        'service_id' => $service->id,
        'desired_date' => $startsAt->format('Y-m-d'),
        'customer_name' => 'مریم انتظاری',
        'customer_phone' => '09123334455',
        'status' => WaitlistEntry::STATUS_WAITING,
    ]);

    $manager = User::find($business->owner_id);
    $this->actingAs($manager)
        ->patch(route('appointments.status', $appointment), ['status' => Appointment::STATUS_CANCELLED])
        ->assertRedirect()
        ->assertSessionHas('info');

    // The Inertia share closure reads the session lazily — it must expose
    // the same string under page.props.flash.info.
    $this->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->has('props.flash.info')
            ->where('props.flash.info', __('waitlist.slot_freed', ['n' => 1])));
});

it('pins the appointment-status notification locale to the appointment, not the actor', function () {
    Queue::fake();

    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $startsAt = $this->nextOpenDate()->setTimeFromTimeString('10:00');

    // The customer booked in English; the manager works in Persian.
    $appointment = $this->createAppointment($this->addStaff($business), $service, $startsAt, Appointment::STATUS_CONFIRMED);
    $appointment->forceFill(['locale' => 'en'])->save();

    // Run through the manager's UI with locale=fa — the worker would otherwise
    // build the notification payload in fa, ignoring the customer's English.
    app()->setLocale('fa');
    $payload = (new AppointmentStatusChanged($appointment))->toArray(User::factory()->create());

    expect($payload['title'])->toBe('Your appointment status changed');
});

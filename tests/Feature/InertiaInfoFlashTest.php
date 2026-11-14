<?php

use App\Models\User;
use App\Notifications\AppointmentStatusChanged;

it('exposes the info flash through the inertia share to the frontend', function () {
    $business = $this->makeBusiness();
    $manager = User::find($business->owner_id);

    $message = __('waitlist.slot_freed', ['n' => 3]);

    // Seed the exact flash key the waitlist flow writes, then verify the
    // shared Inertia props carry it — this is the integration point the
    // fix touched (HandleInertiaRequests::share()).
    $this->actingAs($manager)
        ->withSession(['info' => $message])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('flash.info')
            ->where('flash.info', $message));
});

it('keeps success and error sharing intact next to the new info key', function () {
    $business = $this->makeBusiness();
    $manager = User::find($business->owner_id);

    $this->actingAs($manager)
        ->withSession([
            'success' => 'saved',
            'error' => 'boom',
            'info' => 'heads up',
        ])
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('flash.success', 'saved')
            ->where('flash.error', 'boom')
            ->where('flash.info', 'heads up'));
});

it('pins the appointment-status notification locale to the appointment, not the actor', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $startsAt = $this->nextOpenDate()->setTimeFromTimeString('10:00');

    // The customer booked in English; the manager works in Persian.
    $appointment = $this->createAppointment($staff, $this->serviceOf($business), $startsAt);
    $appointment->forceFill(['locale' => 'en'])->save();

    // Run through the manager's UI with locale=fa — the worker would
    // otherwise build the notification payload in fa, ignoring the
    // customer's English.
    app()->setLocale('fa');
    $payload = (new AppointmentStatusChanged($appointment))->toArray(User::factory()->create());

    expect($payload['title'])->toBe('Your appointment status changed');
});

<?php

use App\Models\Appointment;
use App\Models\User;

it('redirects guests away from the dashboard', function () {
    $this->get(route('appointments.index'))->assertRedirect(route('login'));
});

it('shows a manager only the appointments of their own business', function () {
    $mine = $this->makeBusiness(['name' => 'کسب‌وکار من']);
    $mineAppointment = $this->createAppointment(
        $this->addStaff($mine),
        $this->serviceOf($mine),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
    );

    $other = $this->makeBusiness(['name' => 'کسب‌وکار دیگر']);
    $otherAppointment = $this->createAppointment(
        $this->addStaff($other),
        $this->serviceOf($other),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
    );

    $manager = User::query()->whereKey($mine->owner_id)->firstOrFail();

    $this->actingAs($manager)
        ->get(route('appointments.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Appointments')
            ->where('appointments.total', 1)
            ->where('appointments.data.0.code', $mineAppointment->code)
        );
});

it('lets a manager confirm a pending appointment', function () {
    $business = $this->makeBusiness();
    $appointment = $this->createAppointment(
        $this->addStaff($business),
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
    );

    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();

    $this->actingAs($manager)
        ->patch(route('appointments.status', $appointment), ['status' => Appointment::STATUS_CONFIRMED])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($appointment->refresh()->status)->toBe(Appointment::STATUS_CONFIRMED);
});

it('freezes appointments that reached a final status', function () {
    $business = $this->makeBusiness();
    $appointment = $this->createAppointment(
        $this->addStaff($business),
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_CANCELLED,
    );

    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();

    $this->actingAs($manager)
        ->patch(route('appointments.status', $appointment), ['status' => Appointment::STATUS_COMPLETED])
        ->assertSessionHasErrors('status');

    expect($appointment->refresh()->status)->toBe(Appointment::STATUS_CANCELLED);
});

it('forbids unrelated users from changing appointment status', function () {
    $business = $this->makeBusiness();
    $appointment = $this->createAppointment(
        $this->addStaff($business),
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
    );

    $outsider = User::factory()->create();
    $outsider->assignRole(User::ROLE_CUSTOMER);

    $this->actingAs($outsider)
        ->patch(route('appointments.status', $appointment), ['status' => Appointment::STATUS_CONFIRMED])
        ->assertForbidden();
});

it('allows a manager to create a service', function () {
    $business = $this->makeBusiness();
    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();

    $this->actingAs($manager)
        ->post(route('services.store'), [
            'name' => 'ویزیت تست',
            'duration_minutes' => 30,
            'price' => 250_000,
            'is_active' => true,
            'sort_order' => 5,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($business->services()->where('name', 'ویزیت تست')->exists())->toBeTrue();
});

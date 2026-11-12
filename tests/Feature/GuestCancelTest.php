<?php

use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Support\Carbon;

it('lets a guest cancel their own booking by proving the booking phone', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $appointment = $this->createAppointment(
        $staff,
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_CONFIRMED,
    );

    $this->post(route('bookings.guest_cancel', $appointment->code), [
        'customer_phone' => '09120000000',
    ])
        ->assertRedirect()
        ->assertSessionHas('success', 'نوبت شما لغو شد.');

    expect($appointment->refresh()->status)->toBe(Appointment::STATUS_CANCELLED);
});

it('accepts persian digits and formatting in the phone proof', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $appointment = $this->createAppointment(
        $staff,
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_CONFIRMED,
    );

    $this->post(route('bookings.guest_cancel', $appointment->code), [
        'customer_phone' => '۰۹۱۲ ۰۰۰ ۰۰۰۰',
    ])->assertRedirect();

    expect($appointment->refresh()->status)->toBe(Appointment::STATUS_CANCELLED);
});

it('refuses a guest cancellation when the phone does not match', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $appointment = $this->createAppointment(
        $staff,
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_CONFIRMED,
    );

    $this->post(route('bookings.guest_cancel', $appointment->code), [
        'customer_phone' => '09350000000',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors('customer_phone');

    expect($appointment->refresh()->status)->toBe(Appointment::STATUS_CONFIRMED);
});

it('refuses a guest cancellation for a paid booking', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $appointment = $this->createAppointment(
        $staff,
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_CONFIRMED,
    );
    $this->createPaymentFor($appointment, 500_000, Payment::STATUS_PAID);

    $this->post(route('bookings.guest_cancel', $appointment->code), [
        'customer_phone' => '09120000000',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors('customer_phone');

    expect($appointment->refresh()->status)->toBe(Appointment::STATUS_CONFIRMED);
});

it('refuses a guest cancellation once the appointment time has passed', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $appointment = $this->createAppointment(
        $staff,
        $this->serviceOf($business),
        Carbon::yesterday()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_CONFIRMED,
    );

    $this->post(route('bookings.guest_cancel', $appointment->code), [
        'customer_phone' => '09120000000',
    ])
        ->assertRedirect()
        ->assertSessionHasErrors('customer_phone');

    expect($appointment->refresh()->status)->toBe(Appointment::STATUS_CONFIRMED);
});

it('translates the guest cancellation flash to english', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $appointment = $this->createAppointment(
        $staff,
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_CONFIRMED,
    );

    $this->withCookie('nobat_locale', 'en')
        ->post(route('bookings.guest_cancel', $appointment->code), [
            'customer_phone' => '09120000000',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Your booking has been cancelled.');
});

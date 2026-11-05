<?php

use App\Models\Appointment;
use App\Models\User;

it('lists free slots as json for a service', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $this->addStaff($business);
    $date = $this->nextOpenDate();

    $this->getJson(route('businesses.slots', ['business' => $business->slug, 'service_id' => $service->id, 'date' => $date->format('Y-m-d')]))
        ->assertOk()
        ->assertJsonPath('date', $date->format('Y-m-d'))
        ->assertJsonCount(4, 'slots')
        ->assertJsonPath('slots.0.label', '09:00');
});

it('allows a guest to book an available slot', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $date = $this->nextOpenDate();
    $startsAt = $this->slotAt($date, '10:00');

    $response = $this->post(route('bookings.store', $business->slug), $this->validBookingPayload($business, $service, $startsAt, $staff->id));

    $response->assertRedirect();

    $appointment = Appointment::query()->where('customer_phone', '09121234567')->firstOrFail();

    expect($appointment->status)->toBe(Appointment::STATUS_PENDING)
        ->and($appointment->staff_id)->toBe($staff->id)
        ->and($appointment->starts_at->format('H:i'))->toBe('10:00');

    $this->get(route('bookings.show', $appointment->code))
        ->assertOk()
        ->assertSee($appointment->code);
});

it('validates the customer phone number', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $date = $this->nextOpenDate();

    $payload = $this->validBookingPayload($business, $service, $this->slotAt($date, '10:00'), $staff->id);
    $payload['customer_phone'] = '123456';

    $this->post(route('bookings.store', $business->slug), $payload)
        ->assertSessionHasErrors('customer_phone');

    expect(Appointment::count())->toBe(0);
});

it('prevents booking the same staff slot twice', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $date = $this->nextOpenDate();
    $startsAt = $this->slotAt($date, '10:00');

    $this->createAppointment($staff, $service, $startsAt);

    $this->post(route('bookings.store', $business->slug), $this->validBookingPayload($business, $service, $startsAt, $staff->id))
        ->assertSessionHasErrors('starts_at');

    expect(Appointment::query()->where('customer_phone', '09121234567')->count())->toBe(0);
});

it('auto assigns another free staff member when none is specified', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $first = $this->addStaff($business, 'همکار اول');
    $second = $this->addStaff($business, 'همکار دوم');
    $date = $this->nextOpenDate();
    $startsAt = $this->slotAt($date, '10:00');

    $this->post(route('bookings.store', $business->slug), $this->validBookingPayload($business, $service, $startsAt));

    $this->post(route('bookings.store', $business->slug), $this->validBookingPayload($business, $service, $startsAt));

    $assigned = Appointment::query()->where('customer_phone', '09121234567')->pluck('staff_id');

    expect($assigned)->toHaveCount(2)
        ->and($assigned)->toContain($first->id)
        ->and($assigned)->toContain($second->id);
});

it('links the booking to the logged in customer', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $this->addStaff($business);
    $date = $this->nextOpenDate();

    $customer = User::factory()->create();
    $customer->assignRole(User::ROLE_CUSTOMER);

    $this->actingAs($customer)
        ->post(route('bookings.store', $business->slug), $this->validBookingPayload($business, $service, $this->slotAt($date, '10:00')))
        ->assertRedirect();

    $appointment = Appointment::query()->where('customer_phone', '09121234567')->firstOrFail();

    expect($appointment->user_id)->toBe($customer->id);
});

it('attaches validation errors in persian', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $date = $this->nextOpenDate();

    $payload = $this->validBookingPayload($business, $service, $this->slotAt($date, '10:00'));
    $payload['customer_name'] = '';

    $response = $this->post(route('bookings.store', $business->slug), $payload);

    $response->assertSessionHasErrors('customer_name');
    expect(session('errors')->first('customer_name'))->toContain('الزامی');
});

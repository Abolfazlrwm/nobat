<?php

use App\Models\Appointment;
use App\Models\DiscountCode;
use App\Models\DiscountRedemption;
use App\Models\User;
use App\Services\Payment\PaymentService;

it('applies a percent code to a booking and discounts the invoice', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $date = $this->nextOpenDate();

    $business->discountCodes()->create([
        'code' => 'WELCOME10',
        'type' => DiscountCode::TYPE_PERCENT,
        'value' => 10,
        'is_active' => true,
    ]);

    $payload = $this->validBookingPayload($business, $service, $this->slotAt($date, '10:00'), $staff->id);
    $payload['discount_code'] = 'welcome10';

    $this->post(route('bookings.store', $business->slug), $payload)->assertRedirect();

    $appointment = Appointment::query()->where('customer_phone', '09121234567')->firstOrFail();

    expect($appointment->discount_amount)->toBe(50_000)
        ->and($appointment->discountCode?->code)->toBe('WELCOME10')
        ->and($appointment->discountCode->used_count)->toBe(1)
        ->and(DiscountRedemption::count())->toBe(1)
        ->and(DiscountRedemption::first()->amount)->toBe(50_000);

    // The invoice only exists after the payment session starts.
    $invoice = app(PaymentService::class)->openInvoice($appointment);

    expect($invoice->amount)->toBe(450_000)
        ->and($invoice->snapshot['discount']['code'])->toBe('WELCOME10')
        ->and($invoice->snapshot['discount']['amount'])->toBe(50_000);
});

it('applies a fixed amount code capped at the service price', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $date = $this->nextOpenDate();

    $business->discountCodes()->create([
        'code' => 'FLAT100K',
        'type' => DiscountCode::TYPE_FIXED,
        'value' => 100_000,
        'is_active' => true,
    ]);

    $payload = $this->validBookingPayload($business, $service, $this->slotAt($date, '09:00'), $staff->id);
    $payload['discount_code'] = 'FLAT100K';

    $this->post(route('bookings.store', $business->slug), $payload)->assertRedirect();

    $appointment = Appointment::query()->where('customer_phone', '09121234567')->firstOrFail();

    expect($appointment->discount_amount)->toBe(100_000);
});

it('rejects unknown inactive and expired codes with a session error', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $date = $this->nextOpenDate();
    $startsAt = $this->slotAt($date, '10:00');

    $business->discountCodes()->create([
        'code' => 'OLD1',
        'type' => DiscountCode::TYPE_PERCENT,
        'value' => 10,
        'is_active' => false,
    ]);

    $business->discountCodes()->create([
        'code' => 'EXPIRED',
        'type' => DiscountCode::TYPE_PERCENT,
        'value' => 10,
        'expires_at' => now()->subDay(),
        'is_active' => true,
    ]);

    foreach (['NOPE', 'OLD1', 'EXPIRED'] as $code) {
        $payload = $this->validBookingPayload($business, $service, $startsAt, $staff->id);
        $payload['discount_code'] = $code;

        $this->post(route('bookings.store', $business->slug), $payload)
            ->assertSessionHasErrors('discount_code');

        expect(Appointment::count())->toBe(0);
    }
});

it('rejects codes whose minimum amount is not met', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $date = $this->nextOpenDate();

    $business->discountCodes()->create([
        'code' => 'BIGSPEND',
        'type' => DiscountCode::TYPE_PERCENT,
        'value' => 20,
        'min_amount' => 1_000_000,
        'is_active' => true,
    ]);

    $payload = $this->validBookingPayload($business, $service, $this->slotAt($date, '09:00'), $staff->id);
    $payload['discount_code'] = 'BIGSPEND';

    $this->post(route('bookings.store', $business->slug), $payload)
        ->assertSessionHasErrors('discount_code');

    expect(Appointment::count())->toBe(0);
});

it('enforces the total usage cap of a code', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $date = $this->nextOpenDate();

    $business->discountCodes()->create([
        'code' => 'ONESHOT',
        'type' => DiscountCode::TYPE_PERCENT,
        'value' => 50,
        'max_uses' => 1,
        'is_active' => true,
    ]);

    $payload = $this->validBookingPayload($business, $service, $this->slotAt($date, '09:00'), $staff->id);
    $payload['discount_code'] = 'ONESHOT';

    $this->post(route('bookings.store', $business->slug), $payload)->assertRedirect();
    expect(DiscountCode::first()->used_count)->toBe(1);

    $second = $this->validBookingPayload($business, $service, $this->slotAt($date, '10:00'), $staff->id);
    $second['discount_code'] = 'ONESHOT';
    $second['customer_phone'] = '09129998877';

    $this->post(route('bookings.store', $business->slug), $second)
        ->assertSessionHasErrors('discount_code');

    expect(DiscountCode::first()->used_count)->toBe(1);
});

it('enforces the per-user limit tracked by phone', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $date = $this->nextOpenDate();

    $business->discountCodes()->create([
        'code' => 'TWICE',
        'type' => DiscountCode::TYPE_PERCENT,
        'value' => 10,
        'per_user_limit' => 1,
        'is_active' => true,
    ]);

    $first = $this->validBookingPayload($business, $service, $this->slotAt($date, '09:00'), $staff->id);
    $first['discount_code'] = 'TWICE';

    $this->post(route('bookings.store', $business->slug), $first)->assertRedirect();

    $second = $this->validBookingPayload($business, $service, $this->slotAt($date, '10:00'), $staff->id);
    $second['discount_code'] = 'TWICE';

    $this->post(route('bookings.store', $business->slug), $second)
        ->assertSessionHasErrors('discount_code');
});

it('validates a code through the public check endpoint', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);

    $business->discountCodes()->create([
        'code' => 'WELCOME10',
        'type' => DiscountCode::TYPE_PERCENT,
        'value' => 10,
        'is_active' => true,
    ]);

    $this->getJson(route('businesses.discount', [
        'business' => $business->slug,
        'code' => 'WELCOME10',
        'service_id' => $service->id,
    ]))
        ->assertOk()
        ->assertJsonPath('valid', true)
        ->assertJsonPath('discount_amount', 50_000)
        ->assertJsonPath('final_amount', 450_000);

    $this->getJson(route('businesses.discount', [
        'business' => $business->slug,
        'code' => 'NOPE',
        'service_id' => $service->id,
    ]))
        ->assertOk()
        ->assertJsonPath('valid', false);
});

it('lets managers create toggle and edit codes through the dashboard', function () {
    $business = $this->makeBusiness();
    $manager = User::factory()->create();
    $manager->assignRole(User::ROLE_MANAGER);
    $business->update(['owner_id' => $manager->id]);

    $this->actingAs($manager)->post(route('discounts.store'), [
        'code' => 'newyear',
        'type' => 'percent',
        'value' => 25,
        'max_uses' => 100,
        'per_user_limit' => 2,
        'is_active' => true,
    ])->assertRedirect();

    $code = DiscountCode::firstOrFail();
    expect($code->code)->toBe('NEWYEAR')
        ->and($code->value)->toBe(25)
        ->and($code->business_id)->toBe($business->id);

    $this->actingAs($manager)->patch(route('discounts.toggle', $code))->assertRedirect();
    expect($code->fresh()->is_active)->toBeFalse();

    $this->actingAs($manager)->put(route('discounts.update', $code), [
        'code' => 'NEWYEAR',
        'type' => 'fixed',
        'value' => 250_000,
        'is_active' => true,
    ])->assertRedirect();

    expect($code->fresh()->type)->toBe(DiscountCode::TYPE_FIXED)
        ->and($code->fresh()->is_active)->toBeTrue();
});

it('blocks deleting a code that has already been used', function () {
    $business = $this->makeBusiness();
    $manager = User::factory()->create();
    $manager->assignRole(User::ROLE_MANAGER);
    $business->update(['owner_id' => $manager->id]);

    $code = $business->discountCodes()->create([
        'code' => 'USEDUP',
        'type' => DiscountCode::TYPE_PERCENT,
        'value' => 10,
        'is_active' => true,
    ]);

    // used_count is not mass-assignable; mirror a redeemed code directly.
    $code->forceFill(['used_count' => 3])->save();

    $this->actingAs($manager)->delete(route('discounts.destroy', $code))->assertRedirect();

    expect(DiscountCode::count())->toBe(1);

    // A fresh, unused code can be removed.
    $fresh = $business->discountCodes()->create([
        'code' => 'FRESH',
        'type' => DiscountCode::TYPE_PERCENT,
        'value' => 5,
        'is_active' => true,
    ]);

    $this->actingAs($manager)->delete(route('discounts.destroy', $fresh))->assertRedirect();
    expect(DiscountCode::where('code', 'FRESH')->exists())->toBeFalse();
});

it('never grants a percent discount beyond the purchase amount', function () {
    $business = $this->makeBusiness();

    $code = $business->discountCodes()->create([
        'code' => 'HUGE',
        'type' => DiscountCode::TYPE_FIXED,
        'value' => 2_000_000,
        'is_active' => true,
    ]);

    expect($code->discountFor(500_000))->toBe(500_000);
});

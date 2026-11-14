<?php

use App\Models\Appointment;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Refund;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

covers(PaymentService::class);

it('creates a payment hold for a paid business', function () {
    $business = $this->makeBusiness(['payment_required' => true, 'payment_deadline_minutes' => 15]);
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $startsAt = $this->slotAt($this->nextOpenDate(), '10:00');

    $this->post(route('bookings.store', $business->slug), $this->validBookingPayload($business, $service, $startsAt, $staff->id))
        ->assertRedirect();

    $appointment = Appointment::query()->where('customer_phone', '09121234567')->firstOrFail();

    expect($appointment->status)->toBe(Appointment::STATUS_PENDING_PAYMENT)
        ->and($appointment->payment_expires_at)->not->toBeNull()
        // the invoice is issued when the customer actually starts paying
        ->and($appointment->invoice()->count())->toBe(0)
        ->and($appointment->payments()->count())->toBe(0);
});

it('issues the invoice and opens a gateway session on initiate', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld(amount: 500_000);

    $this->get(route('payments.initiate', $appointment->code))->assertRedirect();

    $invoice = $appointment->refresh()->invoice;

    expect($invoice)->not->toBeNull()
        ->and($invoice->status)->toBe(Invoice::STATUS_ISSUED)
        ->and($invoice->amount)->toBe($service->price)
        ->and($invoice->snapshot['service']['name'])->toBe($service->name)
        ->and($invoice->snapshot['appointment_code'])->toBe($appointment->code)
        ->and($invoice->number)->not->toBeEmpty()
        ->and($payment->refresh()->status)->toBe(Payment::STATUS_AUTHORIZED)
        ->and($payment->authority)->not->toBeNull()
        ->and($payment->invoice_id)->toBe($invoice->id);
});

it('blocks the held slot for other customers until payment', function () {
    $business = $this->makeBusiness(['payment_required' => true, 'payment_deadline_minutes' => 15]);
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $date = $this->nextOpenDate();
    $startsAt = $this->slotAt($date, '10:00');

    $this->post(route('bookings.store', $business->slug), $this->validBookingPayload($business, $service, $startsAt, $staff->id));

    $labels = $this->getJson(route('businesses.slots', ['business' => $business->slug, 'service_id' => $service->id, 'date' => $date->format('Y-m-d')]))
        ->assertOk()
        ->json('slots.*.label');

    expect($labels)->not->toContain('10:00')
        ->and($labels)->toContain('09:00');
});

it('completes the cycle after a successful gateway callback', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld(amount: 500_000);

    // Step 1: initiate -> gateway session (log driver redirects to our callback)
    $response = $this->get(route('payments.initiate', $appointment->code))
        ->assertRedirect();

    $authority = $payment->refresh()->authority;

    expect($authority)->not->toBeNull()
        ->and($payment->status)->toBe(Payment::STATUS_AUTHORIZED);

    // Step 2: the gateway (or a webhook) calls back with OK
    $this->get(route('payments.callback', ['Authority' => $authority, 'Status' => 'OK']))
        ->assertRedirect(route('bookings.show', $appointment->code));

    $payment->refresh();
    $appointment->refresh();
    $invoice = $appointment->invoice;

    expect($payment->status)->toBe(Payment::STATUS_PAID)
        ->and($payment->ref_id)->not->toBeNull()
        ->and($payment->paid_at)->not->toBeNull()
        ->and($appointment->status)->toBe(Appointment::STATUS_CONFIRMED)
        ->and($invoice->status)->toBe(Invoice::STATUS_PAID)
        ->and($invoice->paid_at)->not->toBeNull();
});

it('never records a payment twice when the webhook repeats', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld();
    $payment->forceFill(['authority' => 'AUTH-DUPLICATE', 'status' => Payment::STATUS_AUTHORIZED])->save();

    // First callback settles the payment.
    $this->get(route('payments.callback', ['Authority' => 'AUTH-DUPLICATE', 'Status' => 'OK']))
        ->assertRedirect();

    $firstRefId = $payment->refresh()->ref_id;

    // The gateway fires the same webhook again (retry, user refresh...).
    $this->get(route('payments.callback', ['Authority' => 'AUTH-DUPLICATE', 'Status' => 'OK']))
        ->assertRedirect();
    $this->get(route('payments.callback', ['Authority' => 'AUTH-DUPLICATE', 'Status' => 'OK']))
        ->assertRedirect();

    expect(Payment::query()->where('status', Payment::STATUS_PAID)->count())->toBe(1)
        ->and($payment->refresh()->ref_id)->toBe($firstRefId)
        ->and($appointment->refresh()->status)->toBe(Appointment::STATUS_CONFIRMED)
        ->and(Refund::count())->toBe(0);
});

it('releases expired holds and frees the slot', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld();

    $this->artisan('nobat:release-expired-holds')->expectsOutput('No expired holds.');

    // Travel past the payment deadline.
    $this->travel(20)->minutes();

    $this->artisan('nobat:release-expired-holds');

    $appointment->refresh();

    expect($appointment->status)->toBe(Appointment::STATUS_EXPIRED)
        ->and($appointment->invoice->status)->toBe(Invoice::STATUS_CANCELED)
        ->and($payment->refresh()->status)->toBe(Payment::STATUS_CANCELED);

    $labels = $this->getJson(route('businesses.slots', ['business' => $business->slug, 'service_id' => $service->id, 'date' => $appointment->starts_at->format('Y-m-d')]))
        ->json('slots.*.label');

    expect($labels)->toContain('10:00');
});

it('auto-refunds when the money arrives after the hold expired', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld();
    $payment->forceFill(['authority' => 'AUTH-LATE', 'status' => Payment::STATUS_AUTHORIZED])->save();

    // The deadline passes while the customer sits on the gateway page.
    $this->travel(20)->minutes();

    $this->get(route('payments.callback', ['Authority' => 'AUTH-LATE', 'Status' => 'OK']))
        ->assertRedirect(route('bookings.show', $appointment->code));

    $payment->refresh();
    $refund = $payment->refunds()->first();

    expect($payment->status)->toBe(Payment::STATUS_REFUNDED)
        ->and($refund)->not->toBeNull()
        ->and($refund->status)->toBe(Refund::STATUS_SUCCEEDED)
        ->and($refund->amount)->toBe($payment->amount)
        ->and($payment->invoice->status)->toBe(Invoice::STATUS_REFUNDED)
        ->and($appointment->refresh()->status)->toBe(Appointment::STATUS_PENDING_PAYMENT);
});

it('reverses the payment when a manager cancels a paid appointment', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld();
    $payment->forceFill(['authority' => 'AUTH-CANCEL', 'status' => Payment::STATUS_AUTHORIZED])->save();

    $this->get(route('payments.callback', ['Authority' => 'AUTH-CANCEL', 'Status' => 'OK']));

    expect($appointment->refresh()->status)->toBe(Appointment::STATUS_CONFIRMED);

    $this->actingAs($business->owner)
        ->patch(route('appointments.status', $appointment->id), ['status' => Appointment::STATUS_CANCELLED])
        ->assertRedirect();

    $payment->refresh();

    expect($appointment->refresh()->status)->toBe(Appointment::STATUS_CANCELLED)
        ->and($payment->status)->toBe(Payment::STATUS_REFUNDED)
        ->and(Refund::where('status', Refund::STATUS_SUCCEEDED)->count())->toBe(1);
});

it('renders the printable invoice from the frozen snapshot', function () {
    [$business, $service, $staff, $appointment, $payment] = $this->paidBookingWorld();

    $invoice = $appointment->invoice()->create([
        'number' => (string) Str::ulid(),
        'business_id' => $business->id,
        'amount' => $service->price,
        'currency' => 'IRT',
        'status' => Invoice::STATUS_ISSUED,
        'snapshot' => [
            'business' => ['name' => $business->name, 'phone' => $business->phone, 'address' => $business->address],
            'service' => ['name' => $service->name, 'duration_minutes' => $service->duration_minutes, 'price' => $service->price],
            'customer_name' => $appointment->customer_name,
            'customer_phone' => $appointment->customer_phone,
            'appointment_code' => $appointment->code,
            'starts_at' => $appointment->starts_at->format('Y-m-d\TH:i:s'),
        ],
        'issued_at' => now(),
        'due_at' => $appointment->payment_expires_at,
    ]);

    $this->get(URL::signedRoute('invoices.show', $invoice->number, now()->addDays(7)))
        ->assertOk()
        ->assertSee($invoice->number)
        ->assertSee($business->name)
        ->assertSee($service->name);
});

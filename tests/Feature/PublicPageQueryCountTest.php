<?php

use App\Models\Appointment;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Reset the strict-mode flag after every test in this file — it is a
// process-wide static, and a failed assertion must never leak strict
// lazy-loading into a later test running in the same worker.
afterEach(function () {
    Model::preventLazyLoading(false);
});

// The v1.17.0 perf fix collapsed the lazy queries on Nobat's two hottest
// public pages into the eager-load plan. Model::preventLazyLoading() turns
// any relation resolved AFTER the initial load into a thrown violation, so
// these tests fail the moment someone adds a lazy `$appointment->review`
// or `$payment->invoice` back to a response payload.

it('renders a public business page without any lazy-loaded relation', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);

    // Six approved reviews (one per appointment — the reviews table
    // enforces appointment_id + is_approved) plus a pending one to make
    // sure the count and the limit(6) interact correctly.
    for ($i = 0; $i < 6; $i++) {
        $appointment = $this->createAppointment(
            $staff,
            $service,
            $this->nextOpenDate()->addDays($i)->setTimeFromTimeString('10:00'),
            Appointment::STATUS_COMPLETED,
        );

        $appointment->review()->create([
            'business_id' => $business->id,
            'customer_name' => 'مشتری '.$i,
            'rating' => 5,
            'comment' => 'نظر '.$i,
            'is_approved' => true,
        ]);
    }

    // The pending review must not leak into the approved aggregates.
    $pendingAppointment = $this->createAppointment(
        $staff,
        $service,
        $this->nextOpenDate()->addDays(7)->setTimeFromTimeString('10:00'),
        Appointment::STATUS_COMPLETED,
    );
    $pendingAppointment->review()->create([
        'business_id' => $business->id,
        'customer_name' => 'مشتری در انتظار',
        'rating' => 4,
        'comment' => 'در انتظار',
        'is_approved' => false,
    ]);

    // Three gallery photos — one marked cover.
    $business->photos()->create(['path' => 'covers/a.jpg', 'is_cover' => true, 'sort_order' => 1]);
    $business->photos()->create(['path' => 'gallery/b.jpg', 'is_cover' => false, 'sort_order' => 2]);
    $business->photos()->create(['path' => 'gallery/c.jpg', 'is_cover' => false, 'sort_order' => 3]);

    Model::preventLazyLoading(true);

    $response = $this->get(route('businesses.show', $business->slug));

    $response->assertOk();
    Model::preventLazyLoading(false);

    $response->assertInertia(fn ($page) => $page
        ->where('business.reviews_summary.count', 6)
        ->where('business.reviews_summary.average', 5.0)
        ->where('business.photos.0.is_cover', true)
        ->where('business.reviews', fn ($reviews) => count($reviews) === 6));
});

it('renders a public business page from a bounded query plan', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);

    for ($i = 0; $i < 6; $i++) {
        $appointment = $this->createAppointment(
            $staff,
            $service,
            $this->nextOpenDate()->addDays($i)->setTimeFromTimeString('10:00'),
            Appointment::STATUS_COMPLETED,
        );

        $appointment->review()->create([
            'business_id' => $business->id,
            'customer_name' => 'مشتری '.$i,
            'rating' => 5,
            'comment' => 'نظر '.$i,
            'is_approved' => true,
        ]);
    }

    $business->photos()->create(['path' => 'covers/a.jpg', 'is_cover' => true, 'sort_order' => 1]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->get(route('businesses.show', $business->slug))->assertOk();

    $queryCount = count(DB::getQueryLog());

    // One route bind + services + staff + photos + latest-6 reviews +
    // avg aggregate + count aggregate — the plan is bounded even as the
    // page grows; the old implementation's separate approvedReviews()
    // calls fit the same bound but through scattered one-off queries.
    expect($queryCount)->toBeLessThan(10);
});

it('renders the booking confirmation page without any lazy-loaded relation', function () {
    $business = $this->makeBusiness(['payment_required' => false]);
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);

    $appointment = $this->createAppointment($staff, $service, $this->nextOpenDate()->setTimeFromTimeString('10:00'), Appointment::STATUS_CONFIRMED);

    // A paid payment plus its invoice — the old code lazy-loaded
    // $payment->invoice?->number on the response payload.
    $appointment->payments()->create([
        'gateway' => 'zarinpal',
        'amount' => $service->price,
        'status' => Payment::STATUS_PAID,
        'idempotency_key' => (string) Str::ulid(),
        'authority' => 'AUTH-'.Str::ulid(),
        'ref_id' => 'REF-1',
        'paid_at' => now(),
    ]);
    $appointment->invoice()->create([
        'number' => 'INV-NPLUS1-TEST',
        'business_id' => $business->id,
        'amount' => $service->price,
        'currency' => 'IRT',
        'status' => 'paid',
        'snapshot' => [],
        'issued_at' => now(),
        'paid_at' => now(),
    ]);

    // And a review — the old code ran ->exists() then ->first().
    $appointment->review()->create([
        'business_id' => $business->id,
        'customer_name' => 'مشتری',
        'rating' => 5,
        'comment' => 'عالی',
        'is_approved' => true,
    ]);

    Model::preventLazyLoading(true);

    $response = $this->get(route('bookings.show', $appointment->code));

    $response->assertOk();
    Model::preventLazyLoading(false);

    $response->assertInertia(fn ($page) => $page
        ->where('appointment.has_review', true)
        ->where('appointment.payment.invoice_number', 'INV-NPLUS1-TEST')
        ->where('appointment.review.rating', 5));
});

it('keeps rendering a guest booking page when neither review nor payment exists', function () {
    $business = $this->makeBusiness(['payment_required' => false]);
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);

    $appointment = $this->createAppointment($staff, $service, $this->nextOpenDate()->setTimeFromTimeString('10:00'), Appointment::STATUS_CONFIRMED);

    Model::preventLazyLoading(true);

    $response = $this->get(route('bookings.show', $appointment->code));

    $response->assertOk();
    Model::preventLazyLoading(false);

    $response->assertInertia(fn ($page) => $page
        ->where('appointment.has_review', false)
        ->where('appointment.payment', null));
});

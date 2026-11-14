<?php

use App\Models\Appointment;
use App\Models\Payment;
use App\Models\Review;
use Illuminate\Support\Facades\DB;

it('renders a public business page in a single query plan per relation', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);

    // Six approved reviews + a couple of pending ones to make sure the
    // count and the limit(6) interact correctly.
    for ($i = 0; $i < 6; $i++) {
        $business->reviews()->create([
            'customer_name' => 'مشتری '.$i,
            'customer_phone' => '09120000'.$i.'00',
            'rating' => 5,
            'comment' => 'نظر '.$i,
            'status' => 'approved',
        ]);
    }
    $business->reviews()->create([
        'customer_name' => 'مشتری در انتظار',
        'customer_phone' => '09120000099',
        'rating' => 4,
        'comment' => 'در انتظار',
        'status' => 'pending',
    ]);

    // Three gallery photos — one marked cover.
    $business->photos()->create(['path' => 'covers/a.jpg', 'is_cover' => true, 'sort_order' => 1]);
    $business->photos()->create(['path' => 'gallery/b.jpg', 'is_cover' => false, 'sort_order' => 2]);
    $business->photos()->create(['path' => 'gallery/c.jpg', 'is_cover' => false, 'sort_order' => 3]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->get(route('businesses.show', $business->slug));
    $response->assertOk();

    $queries = DB::getQueryLog();
    $queryCount = count($queries);

    // The previous implementation ran three separate approvedReviews()
    // queries (limit(6)->get(), avg(), count()). After the eager-load
    // refactor, the whole page must render in well under 10 queries:
    //   1. the business row
    //   2. active services
    //   3. active staff
    //   4. photos
    //   5. the latest 6 approved reviews (eager-loaded, limited)
    //   6. the avg aggregate
    //   7. the count aggregate
    //   (plus a few session/auth checks Inertia runs)
    expect($queryCount)->toBeLessThan(10);

    // The cover photo leads the rendered grid, the average and count are
    // both present.
    $response->assertInertia(fn ($page) => $page
        ->where('business.reviews_summary.count', 6)
        ->where('business.reviews_summary.average', 5.0)
        ->where('business.photos.0.is_cover', true));
});

it('renders the booking confirmation page without N+1 on review and payment.invoice', function () {
    $business = $this->makeBusiness(['payment_required' => false]);
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);

    $appointment = $this->createAppointment($staff, $service, $this->nextOpenDate()->setTimeFromTimeString('10:00'), Appointment::STATUS_CONFIRMED);

    // A paid payment plus its invoice — the old code lazy-loaded
    // $payment->invoice?->number on the response payload.
    $payment = $appointment->payments()->create([
        'gateway' => 'zarinpal',
        'amount' => $service->price,
        'status' => Payment::STATUS_PAID,
        'idempotency_key' => (string) \Illuminate\Support\Str::ulid(),
        'authority' => 'AUTH-'.\Illuminate\Support\Str::ulid(),
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
        'customer_name' => 'مشتری',
        'customer_phone' => $appointment->customer_phone,
        'rating' => 5,
        'comment' => 'عالی',
        'status' => 'approved',
    ]);

    DB::flushQueryLog();
    DB::enableQueryLog();

    $response = $this->get(route('bookings.show', $appointment->code));
    $response->assertOk();

    $queries = DB::getQueryLog();

    // The previous implementation fired three lazy queries after the
    // initial with(): payments->first(), payments.invoice (lazy on the
    // first payment), review->exists() and review->first(). The eager
    // refactor collapses all of those into the with() call — fewer than
    // 8 queries for the whole response, including the auth/session bits.
    expect(count($queries))->toBeLessThan(8);

    $response->assertInertia(fn ($page) => $page
        ->where('appointment.has_review', true)
        ->where('appointment.payment.invoice_number', 'INV-NPLUS1-TEST')
        ->where('appointment.review.rating', 5));
});

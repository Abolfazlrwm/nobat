<?php

use App\Models\Appointment;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

it('lets a customer rate a completed booking', function () {
    $business = $this->makeBusiness();
    $appointment = $this->createAppointment(
        $this->addStaff($business),
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_COMPLETED,
    );

    $this->post(route('reviews.store', $business->slug), [
        'code' => $appointment->code,
        'rating' => 5,
        'comment' => 'عالی بود، ممنون!',
    ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $review = Review::query()->firstOrFail();

    expect($review->rating)->toBe(5)
        ->and($review->customer_name)->toBe($appointment->customer_name)
        ->and($review->comment)->toBe('عالی بود، ممنون!')
        ->and($review->is_approved)->toBeFalse();
});

it('refuses reviews for bookings that are not completed', function () {
    $business = $this->makeBusiness();
    $appointment = $this->createAppointment(
        $this->addStaff($business),
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_CONFIRMED,
    );

    $this->post(route('reviews.store', $business->slug), [
        'code' => $appointment->code,
        'rating' => 4,
    ])->assertSessionHasErrors('code');

    expect(Review::count())->toBe(0);
});

it('allows only one review per booking', function () {
    $business = $this->makeBusiness();
    $appointment = $this->createAppointment(
        $this->addStaff($business),
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_COMPLETED,
    );

    $payload = ['code' => $appointment->code, 'rating' => 5];

    $this->post(route('reviews.store', $business->slug), $payload)->assertRedirect();
    $this->post(route('reviews.store', $business->slug), $payload)->assertSessionHasErrors('code');

    expect(Review::count())->toBe(1);
});

it('refuses a tracking code from another business', function () {
    $business = $this->makeBusiness();
    $other = $this->makeBusiness(['name' => 'کسب‌وکار دیگر']);

    $appointment = $this->createAppointment(
        $this->addStaff($other),
        $this->serviceOf($other),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_COMPLETED,
    );

    $this->post(route('reviews.store', $business->slug), [
        'code' => $appointment->code,
        'rating' => 3,
    ])->assertSessionHasErrors('code');

    expect(Review::count())->toBe(0);
});

it('shows the rating on the business page only after moderation', function () {
    $business = $this->makeBusiness(['name' => 'کلینیک ستاره']);
    $appointment = $this->createAppointment(
        $this->addStaff($business),
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_COMPLETED,
    );

    $this->post(route('reviews.store', $business->slug), [
        'code' => $appointment->code,
        'rating' => 5,
        'comment' => 'زمان‌بندی دقیق بود',
    ]);

    // pending review is invisible on the public page
    $this->get(route('businesses.show', $business->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('business.reviews_summary.average', null)
            ->where('business.reviews', [])
        );

    $review = Review::query()->firstOrFail();

    // manager approves it from the dashboard
    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();
    $this->actingAs($manager)
        ->patch(route('reviews.approve', $review))
        ->assertRedirect();

    Cache::forget('nobat.directory');

    expect($review->fresh()->is_approved)->toBeTrue();

    $this->get(route('businesses.show', $business->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('business.reviews_summary.average', 5)
            ->where('business.reviews_summary.count', 1)
            ->where('business.reviews.0.comment', 'زمان‌بندی دقیق بود')
        );
});

it('lists reviews for the manager and hides other businesses', function () {
    $business = $this->makeBusiness();
    $appointment = $this->createAppointment(
        $this->addStaff($business),
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_COMPLETED,
    );

    Review::query()->create([
        'business_id' => $business->id,
        'appointment_id' => $appointment->id,
        'customer_name' => $appointment->customer_name,
        'rating' => 4,
        'comment' => 'خوب بود',
        'is_approved' => false,
    ]);

    $other = $this->makeBusiness(['name' => 'کسب‌وکار دیگر']);

    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();
    $this->actingAs($manager)
        ->get(route('reviews.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Reviews')
            ->where('reviews.0.rating', 4)
            ->where('stats.pending', 1)
        );

    // a foreign review must not be approvable by this manager
    $foreignAppointment = $this->createAppointment(
        $this->addStaff($other),
        $this->serviceOf($other),
        $this->nextOpenDate()->setTimeFromTimeString('11:00'),
        Appointment::STATUS_COMPLETED,
    );
    $foreign = Review::query()->create([
        'business_id' => $other->id,
        'appointment_id' => $foreignAppointment->id,
        'customer_name' => 'مشتری دیگر',
        'rating' => 1,
        'is_approved' => false,
    ]);

    $this->patch(route('reviews.approve', $foreign))->assertNotFound();
});

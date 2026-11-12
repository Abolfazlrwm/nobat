<?php

use App\Models\Appointment;
use App\Models\Review;
use App\Models\User;

it('lets the manager answer a review and shows it publicly', function () {
    $world = function (): array {
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
            'comment' => 'خیلی خوب بود',
        ])->assertRedirect();

        $review = Review::query()->firstOrFail();
        $review->update(['is_approved' => true]);

        return [$business, $review];
    };

    [$business, $review] = $world();

    $manager = User::find($business->owner_id);
    $this->actingAs($manager)
        ->patch(route('reviews.reply', $review), ['reply' => 'ممنون از حسن انتخاب شما؛ منتظر دیدنتان هستیم.'])
        ->assertRedirect()
        ->assertSessionHas('success', 'پاسخ به نظر ثبت شد.');

    expect($review->refresh()->reply)->toBe('ممنون از حسن انتخاب شما؛ منتظر دیدنتان هستیم.')
        ->and($review->replied_at)->not->toBeNull();

    $this->get(route('businesses.show', $business->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('business.reviews.0.reply', 'ممنون از حسن انتخاب شما؛ منتظر دیدنتان هستیم.'));
});

it('lets the manager edit an existing reply', function () {
    $world = function (): array {
        $business = $this->makeBusiness();
        $appointment = $this->createAppointment(
            $this->addStaff($business),
            $this->serviceOf($business),
            $this->nextOpenDate()->setTimeFromTimeString('10:00'),
            Appointment::STATUS_COMPLETED,
        );

        $this->post(route('reviews.store', $business->slug), [
            'code' => $appointment->code,
            'rating' => 4,
        ])->assertRedirect();

        $review = Review::query()->firstOrFail();
        $review->update(['is_approved' => true, 'reply' => 'پاسخ اولیه', 'replied_at' => now()]);

        return [$business, $review];
    };

    [$business, $review] = $world();

    $manager = User::find($business->owner_id);
    $this->actingAs($manager)
        ->patch(route('reviews.reply', $review), ['reply' => 'پاسخ ویرایش‌شده'])
        ->assertRedirect();

    expect($review->refresh()->reply)->toBe('پاسخ ویرایش‌شده');
});

it('validates the reply text and length', function () {
    $world = function (): array {
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
        ])->assertRedirect();

        $review = Review::query()->firstOrFail();
        $review->update(['is_approved' => true]);

        return [$business, $review];
    };

    [$business, $review] = $world();

    $manager = User::find($business->owner_id);
    $this->actingAs($manager)
        ->patch(route('reviews.reply', $review), ['reply' => ''])
        ->assertSessionHasErrors('reply');

    $this->actingAs($manager)
        ->patch(route('reviews.reply', $review), ['reply' => str_repeat('a', 1001)])
        ->assertSessionHasErrors('reply');

    expect($review->refresh()->reply)->toBeNull();
});

it('keeps other businesses from answering reviews they do not own', function () {
    $world = function (): array {
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
        ])->assertRedirect();

        $review = Review::query()->firstOrFail();
        $review->update(['is_approved' => true]);

        return [$business, $review];
    };

    [$business, $review] = $world();

    $other = $this->makeBusiness();
    $stranger = User::find($other->owner_id);

    $this->actingAs($stranger)
        ->patch(route('reviews.reply', $review), ['reply' => 'hijack'])
        ->assertNotFound();

    expect($review->refresh()->reply)->toBeNull();
});

it('translates the reply flash to english', function () {
    $world = function (): array {
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
        ])->assertRedirect();

        $review = Review::query()->firstOrFail();
        $review->update(['is_approved' => true]);

        return [$business, $review];
    };

    [$business, $review] = $world();

    $manager = User::find($business->owner_id);
    $this->withCookie('nobat_locale', 'en')
        ->actingAs($manager)
        ->patch(route('reviews.reply', $review), ['reply' => 'Thank you for your kind words!'])
        ->assertRedirect()
        ->assertSessionHas('success', 'Reply saved.');
});

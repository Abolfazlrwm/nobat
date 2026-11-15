<?php

use App\Jobs\SendReviewInviteSms;
use App\Models\Appointment;
use App\Models\Review;
use App\Models\User;
use App\Services\Sms\SmsManager;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

it('invites the customer to rate the appointment the moment it completes', function () {
    Queue::fake();

    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $appointment = $this->createAppointment($staff, $this->serviceOf($business), $this->nextOpenDate()->setTimeFromTimeString('10:00'), Appointment::STATUS_CONFIRMED);

    $manager = User::find($business->owner_id);
    $this->actingAs($manager)
        ->patch(route('appointments.status', $appointment), ['status' => Appointment::STATUS_COMPLETED])
        ->assertRedirect();

    Queue::assertPushed(SendReviewInviteSms::class, 1);
    Queue::assertPushed(SendReviewInviteSms::class, fn (SendReviewInviteSms $job) => $job->appointment->is($appointment)
        && $job->reviewUrl === route('businesses.show', $business->slug)
        && $job->locale === app()->getLocale());

    expect($appointment->refresh()->review_invited_at)->not->toBeNull();
});

it('never invites a customer who already reviewed', function () {
    Queue::fake();

    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $appointment = $this->createAppointment($staff, $this->serviceOf($business), $this->nextOpenDate()->setTimeFromTimeString('10:00'), Appointment::STATUS_CONFIRMED);

    Review::query()->create([
        'business_id' => $business->id,
        'appointment_id' => $appointment->id,
        'customer_name' => $appointment->customer_name,
        'rating' => 5,
        'is_approved' => false,
    ]);

    $manager = User::find($business->owner_id);
    $this->actingAs($manager)
        ->patch(route('appointments.status', $appointment), ['status' => Appointment::STATUS_COMPLETED])
        ->assertRedirect();

    Queue::assertNotPushed(SendReviewInviteSms::class);
    expect($appointment->refresh()->review_invited_at)->toBeNull();
});

it('does not invite after a cancellation or a no-show', function () {
    Queue::fake();

    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $day = $this->nextOpenDate();

    $cancelled = $this->createAppointment($staff, $this->serviceOf($business), $day->setTimeFromTimeString('10:00'), Appointment::STATUS_CONFIRMED);
    $noShow = $this->createAppointment($staff, $this->serviceOf($business), $day->setTimeFromTimeString('11:00'), Appointment::STATUS_CONFIRMED);

    $manager = User::find($business->owner_id);
    $this->actingAs($manager)
        ->patch(route('appointments.status', $cancelled), ['status' => Appointment::STATUS_CANCELLED])
        ->assertRedirect();
    $this->actingAs($manager)
        ->patch(route('appointments.status', $noShow), ['status' => Appointment::STATUS_NO_SHOW])
        ->assertRedirect();

    Queue::assertNotPushed(SendReviewInviteSms::class);
});

it('pins the invitation to the language the booking was made in', function () {
    Queue::fake();

    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $appointment = $this->createAppointment($staff, $this->serviceOf($business), $this->nextOpenDate()->setTimeFromTimeString('10:00'), Appointment::STATUS_CONFIRMED);
    $appointment->forceFill(['locale' => 'en'])->save();

    $manager = User::find($business->owner_id);
    $this->actingAs($manager)
        ->patch(route('appointments.status', $appointment), ['status' => Appointment::STATUS_COMPLETED])
        ->assertRedirect();

    Queue::assertPushed(SendReviewInviteSms::class, fn (SendReviewInviteSms $job) => $job->locale === 'en');
});

it('sends the invitation with the tracking code and stays silent for reviewed appointments', function () {
    Log::spy();

    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $appointment = $this->createAppointment($staff, $this->serviceOf($business), $this->nextOpenDate()->setTimeFromTimeString('10:00'), Appointment::STATUS_CONFIRMED);

    (new SendReviewInviteSms($appointment, route('businesses.show', $business->slug), 'fa'))->handle(app(SmsManager::class));

    Log::shouldHaveReceived('info')
        ->withArgs(fn (string $channel, array $context) => $channel === 'SMS sent'
            && $context['to'] === $appointment->customer_phone
            && str_contains((string) $context['message'], $appointment->code)
            && str_contains((string) $context['message'], $business->name))
        ->once();

    // A review appearing between dispatch and worker run means no nag.
    Log::shouldReceive('info')->never();

    Review::query()->create([
        'business_id' => $business->id,
        'appointment_id' => $appointment->id,
        'customer_name' => $appointment->customer_name,
        'rating' => 4,
        'is_approved' => false,
    ]);

    (new SendReviewInviteSms($appointment, route('businesses.show', $business->slug), 'fa'))->handle(app(SmsManager::class));
});

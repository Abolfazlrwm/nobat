<?php

use App\Jobs\SendWaitlistPromoSms;
use App\Models\Appointment;
use App\Models\User;
use App\Models\WaitlistEntry;
use Illuminate\Support\Facades\Queue;

function waitingEntry($business, $service, string $date, array $overrides = []): WaitlistEntry
{
    return $business->waitlistEntries()->create($overrides + [
        'service_id' => $service->id,
        'desired_date' => $date,
        'customer_name' => 'مریم انتظاری',
        'customer_phone' => '09123334455',
        'status' => WaitlistEntry::STATUS_WAITING,
    ]);
}

it('announces a freed slot to the waiting list and stamps them notified', function () {
    Queue::fake();

    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $startsAt = $this->nextOpenDate()->setTimeFromTimeString('10:00');
    $appointment = $this->createAppointment($this->addStaff($business), $service, $startsAt, Appointment::STATUS_CONFIRMED);

    $first = waitingEntry($business, $service, $startsAt->format('Y-m-d'));
    $second = waitingEntry($business, $service, $startsAt->format('Y-m-d'), ['customer_name' => 'سارا']);
    // not invited: another day, another service, already handled
    waitingEntry($business, $service, $startsAt->copy()->addDays()->format('Y-m-d'));
    waitingEntry($business, $service, $startsAt->format('Y-m-d'), ['status' => WaitlistEntry::STATUS_NOTIFIED]);
    waitingEntry($business, $service, $startsAt->format('Y-m-d'), ['status' => WaitlistEntry::STATUS_CANCELLED]);

    $manager = User::find($business->owner_id);
    $this->actingAs($manager)
        ->patch(route('appointments.status', $appointment), ['status' => Appointment::STATUS_CANCELLED])
        ->assertRedirect()
        ->assertSessionHas('info');

    Queue::assertPushed(SendWaitlistPromoSms::class, 2);

    expect($first->refresh()->status)->toBe(WaitlistEntry::STATUS_NOTIFIED)
        ->and($first->notified_at)->not->toBeNull()
        ->and($second->refresh()->status)->toBe(WaitlistEntry::STATUS_NOTIFIED);
});

it('promotes the waiting list when a guest cancels their booking', function () {
    Queue::fake();

    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $startsAt = $this->nextOpenDate()->setTimeFromTimeString('10:00');
    $appointment = $this->createAppointment($this->addStaff($business), $service, $startsAt, Appointment::STATUS_CONFIRMED);
    waitingEntry($business, $service, $startsAt->format('Y-m-d'));

    $this->post(route('bookings.guest_cancel', $appointment->code), ['customer_phone' => '09120000000'])
        ->assertRedirect();

    Queue::assertPushed(SendWaitlistPromoSms::class, 1);

    Queue::assertPushed(SendWaitlistPromoSms::class, fn (SendWaitlistPromoSms $job) => $job->locale === 'fa'
        && $job->slotUrl === route('businesses.show', $business->slug));
});

it('leaves the waiting list alone when no slot is freed', function () {
    Queue::fake();

    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $startsAt = $this->nextOpenDate()->setTimeFromTimeString('10:00');
    $appointment = $this->createAppointment($this->addStaff($business), $service, $startsAt, Appointment::STATUS_CONFIRMED);

    $manager = User::find($business->owner_id);
    $this->actingAs($manager)
        ->patch(route('appointments.status', $appointment), ['status' => Appointment::STATUS_COMPLETED])
        ->assertRedirect();

    Queue::assertNotPushed(SendWaitlistPromoSms::class);
});

it('pins the promo sms to the locale each entry joined the waitlist in', function () {
    Queue::fake();

    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $startsAt = $this->nextOpenDate()->setTimeFromTimeString('10:00');
    $appointment = $this->createAppointment($this->addStaff($business), $service, $startsAt, Appointment::STATUS_CONFIRMED);

    // Three waiters, each in a different locale — none in the manager's fa.
    $english = waitingEntry($business, $service, $startsAt->format('Y-m-d'), ['customer_phone' => '09121110001', 'locale' => 'en']);
    $arabic = waitingEntry($business, $service, $startsAt->format('Y-m-d'), ['customer_phone' => '09121110002', 'locale' => 'ar']);

    $manager = User::find($business->owner_id);
    $this->actingAs($manager)
        ->patch(route('appointments.status', $appointment), ['status' => Appointment::STATUS_CANCELLED])
        ->assertRedirect();

    Queue::assertPushed(SendWaitlistPromoSms::class, 2);
    Queue::assertPushed(SendWaitlistPromoSms::class, fn (SendWaitlistPromoSms $job) => $job->entry->is($english) && $job->locale === 'en');
    Queue::assertPushed(SendWaitlistPromoSms::class, fn (SendWaitlistPromoSms $job) => $job->entry->is($arabic) && $job->locale === 'ar');
});

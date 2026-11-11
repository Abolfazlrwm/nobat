<?php

use App\Models\Appointment;
use App\Models\User;
use App\Models\WaitlistEntry;

function waitlistPayload($business, $service, string $date, array $overrides = []): array
{
    return $overrides + [
        'service_id' => $service->id,
        'desired_date' => $date,
        'customer_name' => 'مریم انتظاری',
        'customer_phone' => '09123334455',
    ];
}

it('lets a guest join the waitlist for a fully booked day', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $date = $this->nextOpenDate()->format('Y-m-d');

    $this->post(route('waitlist.store', $business->slug), waitlistPayload($business, $service, $date))
        ->assertRedirect()
        ->assertSessionHas('success');

    $entry = WaitlistEntry::firstOrFail();

    expect($entry->status)->toBe(WaitlistEntry::STATUS_WAITING)
        ->and($entry->service_id)->toBe($service->id)
        ->and($entry->desired_date->format('Y-m-d'))->toBe($date)
        ->and($entry->business_id)->toBe($business->id);
});

it('answers json signups with a created payload', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $date = $this->nextOpenDate()->format('Y-m-d');

    $this->postJson(route('waitlist.store', $business->slug), waitlistPayload($business, $service, $date))
        ->assertStatus(201)
        ->assertJsonPath('status', 'joined');
});

it('rejects a duplicate signup for the same day and service', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $date = $this->nextOpenDate()->format('Y-m-d');

    $this->postJson(route('waitlist.store', $business->slug), waitlistPayload($business, $service, $date))
        ->assertStatus(201);

    $this->postJson(route('waitlist.store', $business->slug), waitlistPayload($business, $service, $date))
        ->assertStatus(409)
        ->assertJsonPath('status', 'duplicate');

    expect(WaitlistEntry::count())->toBe(1);
});

it('validates the desired date is today or later', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $date = Carbon\CarbonImmutable::yesterday()->format('Y-m-d');

    $this->post(route('waitlist.store', $business->slug), waitlistPayload($business, $service, $date))
        ->assertSessionHasErrors('desired_date');

    expect(WaitlistEntry::count())->toBe(0);
});

it('shows the waitlist board to the owning manager', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $date = $this->nextOpenDate()->format('Y-m-d');

    $business->waitlistEntries()->create([
        'service_id' => $service->id,
        'desired_date' => $date,
        'customer_name' => 'مریم انتظاری',
        'customer_phone' => '09123334455',
        'status' => WaitlistEntry::STATUS_WAITING,
    ]);

    $manager = User::find($business->owner_id);

    $this->actingAs($manager)->get(route('waitlist.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Waitlist')
            ->has('entries', 1)
            ->where('counts.waiting', 1));
});

it('marks an entry as notified and cancelled', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $manager = User::find($business->owner_id);

    $entry = $business->waitlistEntries()->create([
        'service_id' => $service->id,
        'desired_date' => $this->nextOpenDate()->format('Y-m-d'),
        'customer_name' => 'مریم انتظاری',
        'customer_phone' => '09123334455',
        'status' => WaitlistEntry::STATUS_WAITING,
    ]);

    $this->actingAs($manager)->patchJson(route('waitlist.notify', $entry))->assertRedirect();

    expect($entry->fresh()->status)->toBe(WaitlistEntry::STATUS_NOTIFIED)
        ->and($entry->fresh()->notified_at)->not->toBeNull();

    $this->actingAs($manager)->patchJson(route('waitlist.cancel', $entry))->assertRedirect();

    expect($entry->fresh()->status)->toBe(WaitlistEntry::STATUS_CANCELLED);
});

it('hints the manager about waitlisted customers when a slot frees up', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $date = $this->nextOpenDate();
    $startsAt = $this->slotAt($date, '10:00');

    $appointment = $this->createAppointment($staff, $service, $startsAt);

    $business->waitlistEntries()->create([
        'service_id' => $service->id,
        'desired_date' => $startsAt->format('Y-m-d'),
        'customer_name' => 'مریم انتظاری',
        'customer_phone' => '09123334455',
        'status' => WaitlistEntry::STATUS_WAITING,
    ]);

    $manager = User::find($business->owner_id);

    $this->actingAs($manager)
        ->from(route('appointments.index'))
        ->patch(route('appointments.status', $appointment), ['status' => Appointment::STATUS_CANCELLED])
        ->assertRedirect()
        ->assertSessionHas('info');

    $freed = WaitlistEntry::query()
        ->whereDate('desired_date', $startsAt->format('Y-m-d'))
        ->waiting()
        ->count();

    expect($freed)->toBe(1);
});

it('closes a waitlist entry as booked when that customer books the day', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $date = $this->nextOpenDate()->format('Y-m-d');

    $business->waitlistEntries()->create([
        'service_id' => $service->id,
        'desired_date' => $date,
        'customer_name' => 'مریم انتظاری',
        'customer_phone' => '09123334455',
        'status' => WaitlistEntry::STATUS_WAITING,
    ]);

    $payload = $this->validBookingPayload($business, $service, $this->slotAt($this->nextOpenDate(), '09:00'), $staff->id);
    $payload['customer_phone'] = '09123334455';

    $this->post(route('bookings.store', $business->slug), $payload)->assertRedirect();

    expect(WaitlistEntry::first()->status)->toBe(WaitlistEntry::STATUS_BOOKED);
});

it('keeps other managers out of the waitlist actions', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);

    $entry = $business->waitlistEntries()->create([
        'service_id' => $service->id,
        'desired_date' => $this->nextOpenDate()->format('Y-m-d'),
        'customer_name' => 'مریم انتظاری',
        'customer_phone' => '09123334455',
        'status' => WaitlistEntry::STATUS_WAITING,
    ]);

    $stranger = User::factory()->create();
    $stranger->assignRole(User::ROLE_MANAGER);

    $this->actingAs($stranger)->patchJson(route('waitlist.notify', $entry))->assertForbidden();

    expect($entry->fresh()->status)->toBe(WaitlistEntry::STATUS_WAITING);
});

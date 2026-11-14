<?php

use App\Models\WaitlistEntry;
use Illuminate\Support\Facades\Cache;

it('serializes concurrent waitlist joins so only one row is created', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $date = $this->nextOpenDate()->format('Y-m-d');

    $payload = [
        'service_id' => $service->id,
        'desired_date' => $date,
        'customer_name' => 'مریم انتظاری',
        'customer_phone' => '09123334455',
    ];

    // Pre-acquire the lock the controller waits on; both requests in this
    // test will find it held and take the duplicate-response path — the
    // same path the loser of a real race would take. After releasing,
    // a fresh request succeeds.
    $lock = Cache::lock("waitlist:join:{$payload['customer_phone']}:{$payload['desired_date']}", 10);
    $lock->get();

    $this->post(route('waitlist.store', $business->slug), $payload)
        ->assertRedirect()
        ->assertSessionHas('info'); // duplicate, not success

    expect(WaitlistEntry::count())->toBe(0);

    $lock->release();

    $this->post(route('waitlist.store', $business->slug), $payload)
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(WaitlistEntry::count())->toBe(1);
});

it('still detects a duplicate after a previous join completed', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $date = $this->nextOpenDate()->format('Y-m-d');

    $payload = [
        'service_id' => $service->id,
        'desired_date' => $date,
        'customer_name' => 'مریم انتظاری',
        'customer_phone' => '09123334455',
    ];

    // First join succeeds.
    $this->post(route('waitlist.store', $business->slug), $payload)
        ->assertSessionHas('success');

    // Second identical join is rejected as a duplicate — the lock-free
    // check still works because the row is in the table.
    $this->post(route('waitlist.store', $business->slug), $payload)
        ->assertSessionHas('info');

    expect(WaitlistEntry::count())->toBe(1);
});

it('lets the same phone waitlist again on a different date', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $firstDate = $this->nextOpenDate()->format('Y-m-d');
    $secondDate = $this->nextOpenDate()->addDay()->format('Y-m-d');

    $payload = [
        'service_id' => $service->id,
        'customer_name' => 'مریم انتظاری',
        'customer_phone' => '09123334455',
    ];

    $this->post(route('waitlist.store', $business->slug), $payload + ['desired_date' => $firstDate])
        ->assertSessionHas('success');

    // A different desired_date is a separate waitlist entry — the lock is
    // keyed on phone+date, not just phone, so this must not collide.
    $this->post(route('waitlist.store', $business->slug), $payload + ['desired_date' => $secondDate])
        ->assertSessionHas('success');

    expect(WaitlistEntry::count())->toBe(2);
});

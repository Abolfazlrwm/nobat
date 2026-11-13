<?php

use App\Models\Appointment;
use App\Models\Service;
use App\Models\Staff;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;

function customerBooking(Staff $staff, Service $service, CarbonImmutable $startsAt, string $status, string $name, string $phone): Appointment
{
    return $staff->appointments()->create([
        'code' => (string) Str::ulid(),
        'business_id' => $staff->business_id,
        'service_id' => $service->id,
        'customer_name' => $name,
        'customer_phone' => $phone,
        'starts_at' => $startsAt,
        'ends_at' => $startsAt->addMinutes($service->duration_minutes),
        'status' => $status,
    ]);
}

it('aggregates per-customer spend and no-show stats for the manager', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $day = $this->nextOpenDate();

    // مریم: two completed visits → 1,000,000 spent, zero no-shows.
    customerBooking($staff, $service, $this->slotAt($day, '09:00'), Appointment::STATUS_COMPLETED, 'مریم مصرفی', '09121112233');
    customerBooking($staff, $service, $this->slotAt($day, '11:00'), Appointment::STATUS_COMPLETED, 'مریم مصرفی', '09121112233');

    // رضا: one completed, one no-show → 500,000 spent, 50% no-show rate.
    customerBooking($staff, $service, $this->slotAt($day, '12:00'), Appointment::STATUS_COMPLETED, 'رضا غایب', '09124445566');
    customerBooking($staff, $service, $this->slotAt($day->addDay(), '09:00'), Appointment::STATUS_NO_SHOW, 'رضا غایب', '09124445566');

    $manager = User::find($business->owner_id);

    $this->actingAs($manager)->get(route('customers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Customers')
            ->has('customers.data', 2)
            ->where('stats.total', 2)
            ->where('stats.active', 2)
            ->where('stats.revenue', 1_500_000)
            // Sorted by lifetime spend: مریم first.
            ->where('customers.data.0.name', 'مریم مصرفی')
            ->where('customers.data.0.phone', '09121112233')
            ->where('customers.data.0.appointments_count', 2)
            ->where('customers.data.0.completed_count', 2)
            ->where('customers.data.0.total_spent', 1_000_000)
            ->where('customers.data.0.no_show_rate', 0)
            ->where('customers.data.1.phone', '09124445566')
            ->where('customers.data.1.total_spent', 500_000)
            ->where('customers.data.1.no_show_count', 1)
            ->where('customers.data.1.no_show_rate', 50)
        );
});

it('keeps each manager\'s customer list scoped to their own business', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    customerBooking($staff, $service, $this->slotAt($this->nextOpenDate(), '09:00'), Appointment::STATUS_COMPLETED, 'مریم مصرفی', '09121112233');

    // A second business with its own customer must stay invisible.
    $other = $this->makeBusiness(['name' => 'کلینیک دیگر']);
    $otherService = $this->serviceOf($other);
    $otherStaff = $this->addStaff($other);
    customerBooking($otherStaff, $otherService, $this->slotAt($this->nextOpenDate(), '10:00'), Appointment::STATUS_COMPLETED, 'مشتری دیگر', '09129998877');

    $manager = User::find($business->owner_id);

    $this->actingAs($manager)->get(route('customers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Customers')
            ->has('customers.data', 1)
            ->where('customers.data.0.phone', '09121112233')
        );
});

it('shows a customer profile with the full appointment history', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $day = $this->nextOpenDate();

    customerBooking($staff, $service, $this->slotAt($day, '09:00'), Appointment::STATUS_COMPLETED, 'مریم مصرفی', '09121112233');
    customerBooking($staff, $service, $this->slotAt($day->addDay(), '09:00'), Appointment::STATUS_NO_SHOW, 'مریم مصرفی', '09121112233');
    customerBooking($staff, $service, $this->slotAt($day->addDays(2), '09:00'), Appointment::STATUS_CANCELLED, 'مریم مصرفی', '09121112233');

    $manager = User::find($business->owner_id);

    $this->actingAs($manager)->get(route('customers.show', ['phone' => '09121112233']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/CustomerProfile')
            ->where('customer.name', 'مریم مصرفی')
            ->where('customer.phone', '09121112233')
            ->where('customer.appointments_count', 3)
            ->where('customer.completed_count', 1)
            ->where('customer.cancelled_count', 1)
            ->where('customer.no_show_rate', 50)
            ->where('customer.total_spent', 500_000)
            ->has('history', 3)
            // Newest first: cancelled, no-show, completed.
            ->where('history.0.status', Appointment::STATUS_CANCELLED)
            ->where('history.1.status', Appointment::STATUS_NO_SHOW)
            ->where('history.2.status', Appointment::STATUS_COMPLETED)
            ->where('history.2.price', 500_000)
        );
});

it('answers 404 for a phone that never booked', function () {
    $business = $this->makeBusiness();
    $manager = User::find($business->owner_id);

    $this->actingAs($manager)->get(route('customers.show', ['phone' => '09120000000']))
        ->assertNotFound();
});

it('keeps staff users out of the customer panel', function () {
    $business = $this->makeBusiness();

    $staffUser = User::factory()->create();
    $staffUser->assignRole(User::ROLE_STAFF);
    $this->addStaff($business, 'همکار تست', $staffUser);

    $this->actingAs($staffUser)->get(route('customers.index'))->assertNotFound();
});

it('paginates the customer book at fifteen per page', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);

    // Sixteen distinct customers, one completed booking each.
    $day = $this->nextOpenDate();
    foreach (range(1, 16) as $i) {
        customerBooking($staff, $service, $this->slotAt($day->addDays($i), '09:00'), Appointment::STATUS_COMPLETED, "مشتری {$i}", '0912'.str_pad((string) $i, 7, '0', STR_PAD_LEFT));
    }

    $manager = User::find($business->owner_id);

    $this->actingAs($manager)->get(route('customers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Customers')
            ->has('customers.data', 15)
            ->where('customers.total', 16)
            ->where('customers.last_page', 2)
            ->where('stats.total', 16)
            ->where('stats.revenue', 16 * 500_000)
        );
});

it('filters the customer book with a server-side search', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $staff = $this->addStaff($business);
    $day = $this->nextOpenDate();

    customerBooking($staff, $service, $this->slotAt($day, '09:00'), Appointment::STATUS_COMPLETED, 'مریم مصرفی', '09121112233');
    customerBooking($staff, $service, $this->slotAt($day, '10:00'), Appointment::STATUS_COMPLETED, 'رضا رضایی', '09124445566');

    $manager = User::find($business->owner_id);

    $this->actingAs($manager)->get(route('customers.index', ['q' => 'مریم']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('customers.data', 1)
            ->where('customers.data.0.phone', '09121112233')
            ->where('search', 'مریم')
            ->where('stats.total', 2)
        );
});

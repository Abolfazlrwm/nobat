<?php

use App\Models\Appointment;
use App\Models\DiscountCode;
use App\Models\DiscountRedemption;
use App\Models\User;

it('shows analytics kpis with discount-aware revenue for the manager', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $service = $this->serviceOf($business);

    $code = $business->discountCodes()->create([
        'code' => 'ANALYTICS10',
        'type' => DiscountCode::TYPE_FIXED,
        'value' => 100_000,
        'is_active' => true,
    ]);

    // two completed bookings today (one discounted) and one cancelled
    $first = $this->createAppointment($staff, $service, now()->setTimeFromTimeString('10:00'), Appointment::STATUS_COMPLETED);
    $second = $this->createAppointment($staff, $service, now()->setTimeFromTimeString('11:00'), Appointment::STATUS_COMPLETED);
    $this->createAppointment($staff, $service, now()->setTimeFromTimeString('12:00'), Appointment::STATUS_CANCELLED);

    $second->update(['customer_phone' => '09121111111']);

    DiscountRedemption::query()->create([
        'discount_code_id' => $code->id,
        'appointment_id' => $first->id,
        'customer_phone' => $first->customer_phone,
        'amount' => 100_000,
    ]);

    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();

    $this->actingAs($manager)
        ->get(route('analytics.index', ['days' => 7]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Analytics')
            ->where('stats.revenue', 900_000)
            ->where('stats.bookings', 3)
            ->where('stats.completed', 2)
            ->where('stats.cancelled', 1)
            ->where('stats.completion_rate', 66.7)
            ->where('stats.cancellation_rate', 33.3)
            ->where('stats.avg_ticket', 450_000)
            ->where('stats.unique_customers', 2)
            ->has('series', 7)
            ->has('by_status')
            ->has('top_services')
            ->has('peak_hours')
            ->has('heatmap')
        );
});

it('compares the range against the previous period', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $service = $this->serviceOf($business);

    // previous window (31..60 days ago): one completed booking, current window: two
    $this->createAppointment($staff, $service, now()->subDays(40)->setTimeFromTimeString('10:00'), Appointment::STATUS_COMPLETED);
    $this->createAppointment($staff, $service, now()->subDays(3)->setTimeFromTimeString('10:00'), Appointment::STATUS_COMPLETED);
    $this->createAppointment($staff, $service, now()->subDays(4)->setTimeFromTimeString('11:00'), Appointment::STATUS_COMPLETED);

    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();

    $this->actingAs($manager)
        ->get(route('analytics.index', ['days' => 30]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.revenue', 1_000_000)
            ->where('stats.revenue_delta', 100.0)
            ->where('stats.bookings_delta', 100.0)
        );
});

it('reports a null delta when the previous period has no baseline', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $service = $this->serviceOf($business);

    $this->createAppointment($staff, $service, now()->subDay()->setTimeFromTimeString('10:00'), Appointment::STATUS_COMPLETED);

    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();

    $this->actingAs($manager)
        ->get(route('analytics.index', ['days' => 7]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('stats.revenue', 500_000)
            ->where('stats.revenue_delta', null)
            ->where('stats.bookings_delta', null)
        );
});

it('isolates the analytics of one business from another', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $service = $this->serviceOf($business);

    $this->createAppointment($staff, $service, now()->subDay()->setTimeFromTimeString('10:00'), Appointment::STATUS_COMPLETED);

    $other = $this->makeBusiness(['name' => 'کسب‌وکار دیگر']);
    $otherManager = User::query()->whereKey($other->owner_id)->firstOrFail();

    $this->actingAs($otherManager)
        ->get(route('analytics.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/Analytics')
            ->where('stats.bookings', 0)
            ->where('stats.revenue', 0)
        );
});

it('falls back to 30 days when the range is invalid and supports 90 days', function () {
    $business = $this->makeBusiness();
    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();

    $this->actingAs($manager)
        ->get(route('analytics.index', ['days' => 13]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('days', 30)->has('series', 30));

    $this->actingAs($manager)
        ->get(route('analytics.index', ['days' => 90]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('days', 90)->has('series', 90));
});

it('lets the super admin inspect another business analytics', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $service = $this->serviceOf($business);

    $this->createAppointment($staff, $service, now()->subDay()->setTimeFromTimeString('10:00'), Appointment::STATUS_COMPLETED);

    $admin = User::factory()->create();
    $admin->assignRole(User::ROLE_SUPER_ADMIN);

    $this->actingAs($admin)
        ->get(route('analytics.index', ['business_id' => $business->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('stats.bookings', 1)->where('stats.revenue', 500_000));
});

it('requires authentication for the analytics page', function () {
    $this->get(route('analytics.index'))->assertRedirect(route('login'));
});

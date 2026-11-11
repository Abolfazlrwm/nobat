<?php

use App\Models\User;

it('renders the working-hours board with hydrated time windows', function () {
    $business = $this->makeBusiness();
    $manager = User::query()->whereKey($business->owner_id)->firstOrFail();

    $this->actingAs($manager)
        ->get(route('working-hours.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard/WorkingHours')
            ->where('business.name', $business->name)
            ->where('windows.0.weekday_name', 'شنبه')
            ->where('windows.0.starts_at', '09:00')
            ->where('windows.0.ends_at', '13:00')
        );
});

it('keeps guests away from the working-hours board', function () {
    $this->get(route('working-hours.index'))->assertRedirect(route('login'));
});

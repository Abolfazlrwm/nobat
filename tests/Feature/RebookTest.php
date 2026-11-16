<?php

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Str;

function rebookBusiness(string $name): Business
{
    $owner = User::factory()->create();
    $owner->assignRole(User::ROLE_MANAGER);

    /** @var Business $business */
    $business = $owner->ownedBusinesses()->create([
        'name' => $name,
        'slug' => 'rebook-'.Str::random(6),
        'phone' => '02100000000',
        'is_active' => true,
    ]);

    $business->services()->create([
        'name' => 'خدمت اصلی',
        'duration_minutes' => 60,
        'price' => 300_000,
        'is_active' => true,
    ]);

    $business->staff()->create(['name' => 'همکار تست', 'is_active' => true]);

    return $business;
}

it('preselects the service and staff a returning customer had last time', function () {
    $business = rebookBusiness('سالن آفتاب');
    $service = $business->services->first();
    $staff = $business->staff->first();

    $this->get(route('businesses.show', [
        'business' => $business->slug,
        'service_id' => $service->id,
        'staff_id' => $staff->id,
    ]))
        ->assertOk()
        ->assertInertia(fn (Inertia\Testing\AssertableInertia $page) => $page
            ->where('preset.service_id', $service->id)
            ->where('preset.staff_id', $staff->id));
});

it('opens the wizard blank without a preset', function () {
    $business = rebookBusiness('سالن آفتاب');

    $this->get(route('businesses.show', $business->slug))
        ->assertOk()
        ->assertInertia(fn (Inertia\Testing\AssertableInertia $page) => $page
            ->where('preset.service_id', null)
            ->where('preset.staff_id', null));
});

it('ignores a service id that belongs to another business', function () {
    $business = rebookBusiness('سالن آفتاب');
    $other = rebookBusiness('سالن ماه');

    $this->get(route('businesses.show', [
        'business' => $business->slug,
        'service_id' => $other->services->first()->id,
    ]))
        ->assertOk()
        ->assertInertia(fn (Inertia\Testing\AssertableInertia $page) => $page
            ->where('preset.service_id', null));
});

it('ignores a preset pointing at a deactivated service', function () {
    $business = rebookBusiness('سالن آفتاب');
    $service = $business->services->first();
    $service->update(['is_active' => false]);

    $this->get(route('businesses.show', [
        'business' => $business->slug,
        'service_id' => $service->id,
    ]))
        ->assertOk()
        ->assertInertia(fn (Inertia\Testing\AssertableInertia $page) => $page
            ->where('preset.service_id', null));
});

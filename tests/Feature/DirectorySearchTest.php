<?php

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

function directoryBusiness(string $name, array $overrides = []): Business
{
    $owner = User::factory()->create();
    $owner->assignRole(User::ROLE_MANAGER);

    /** @var Business $business */
    $business = $owner->ownedBusinesses()->create($overrides + [
        'name' => $name,
        'slug' => 'dir-'.\Illuminate\Support\Str::random(6),
        'phone' => '02100000000',
        'is_active' => true,
    ]);

    Cache::forget('nobat.directory');

    return $business;
}

it('filters the directory by a free-text query across name, description and address', function () {
    $salon = directoryBusiness('سالن آفتاب', ['description' => 'کوتاهی مو و زیبایی', 'address' => 'تهران، ونک']);
    $clinic = directoryBusiness('کلینیک مهر', ['description' => 'پوست و مو', 'address' => 'تهران، جانبازان']);

    $this->get(route('home', ['q' => 'مهر']))
        ->assertOk()
        ->assertInertia(fn (Inertia\Testing\AssertableInertia $page) => $page
            ->component('public/Directory')
            ->has('businesses', 1)
            ->where('businesses.0.name', $clinic->name));

    // "تهران" matches both, "ونک" matches only the salon's address line.
    $this->get(route('home', ['q' => 'ونک']))
        ->assertInertia(fn (Inertia\Testing\AssertableInertia $page) => $page
            ->has('businesses', 1)
            ->where('businesses.0.id', $salon->id));

    expect($salon->refresh()->name)->toBe('سالن آفتاب');
});

it('filters the directory by category', function () {
    $salon = directoryBusiness('سالن آفتاب', ['category' => Business::CATEGORY_BEAUTY]);
    directoryBusiness('کلینیک مهر', ['category' => Business::CATEGORY_MEDICAL]);

    $this->get(route('home', ['category' => 'beauty']))
        ->assertOk()
        ->assertInertia(fn (Inertia\Testing\AssertableInertia $page) => $page
            ->has('businesses', 1)
            ->where('businesses.0.id', $salon->id)
            ->where('businesses.0.category_label', __('business_categories.beauty')));

    // An unknown category value is ignored, not fatal.
    $this->get(route('home', ['category' => 'spacecraft']))
        ->assertInertia(fn (Inertia\Testing\AssertableInertia $page) => $page
            ->has('businesses', 2)
            ->where('filters.category', null));
});

it('combines the category and the search query', function () {
    $match = directoryBusiness('سالن آفتاب', ['category' => Business::CATEGORY_BEAUTY]);
    directoryBusiness('آفتاب‌آرا', ['category' => Business::CATEGORY_SERVICES]);

    $this->get(route('home', ['q' => 'آفتاب', 'category' => 'beauty']))
        ->assertInertia(fn (Inertia\Testing\AssertableInertia $page) => $page
            ->has('businesses', 1)
            ->where('businesses.0.id', $match->id));
});

it('exposes the localized category vocabulary to the page', function () {
    directoryBusiness('سالن آفتاب');

    $this->get(route('home'))
        ->assertInertia(fn (Inertia\Testing\AssertableInertia $page) => $page
            ->has('categories', count(Business::CATEGORIES))
            ->where('categories.0.value', Business::CATEGORY_MEDICAL)
            ->where('categories.0.label', __('business_categories.medical'))
            ->where('filters', ['q' => '', 'category' => null]));
});

it('lets the super admin assign a category and busts the directory cache', function () {
    $business = directoryBusiness('سالن آفتاب');
    Cache::remember('nobat.directory', now()->addMinutes(5), fn () => collect());

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(User::ROLE_SUPER_ADMIN);

    $this->actingAs($superAdmin)
        ->patch(route('businesses.category', $business), ['category' => Business::CATEGORY_BEAUTY])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($business->refresh()->category)->toBe(Business::CATEGORY_BEAUTY)
        ->and(Cache::has('nobat.directory'))->toBeFalse();
});

it('refuses a category that is not in the vocabulary', function () {
    $business = directoryBusiness('سالن آفتاب');

    $superAdmin = User::factory()->create();
    $superAdmin->assignRole(User::ROLE_SUPER_ADMIN);

    $this->actingAs($superAdmin)
        ->patch(route('businesses.category', $business), ['category' => 'spacecraft'])
        ->assertSessionHasErrors('category');

    expect($business->refresh()->category)->toBeNull();
});

it('hides category assignment from plain managers', function () {
    $business = directoryBusiness('سالن آفتاب');

    $owner = User::find($business->owner_id);

    $this->actingAs($owner)
        ->patch(route('businesses.category', $business), ['category' => Business::CATEGORY_BEAUTY])
        ->assertForbidden();

    expect($business->refresh()->category)->toBeNull();
});

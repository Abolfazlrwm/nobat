<?php

use App\Models\Appointment;
use Illuminate\Support\Str;

it('serves the persian RTL shell by default', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('lang="fa"', false)
        ->assertSee('dir="rtl"', false);
});

it('shares the resolved locale with the inertia payload', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('locale', 'fa'));

    $this->withCookie('nobat_locale', 'en')
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('locale', 'en'));
});

it('persists a locale choice and rejects unsupported locales', function () {
    $this->post(route('locale.switch'), ['locale' => 'en'])
        ->assertRedirect()
        ->assertCookie('nobat_locale', 'en');

    $this->post(route('locale.switch'), ['locale' => 'de'])
        ->assertSessionHasErrors('locale');
});

it('serves the english LTR shell when the cookie is set', function () {
    $this->withCookie('nobat_locale', 'en')
        ->get(route('home'))
        ->assertOk()
        ->assertSee('lang="en"', false)
        ->assertSee('dir="ltr"', false);
});

it('translates server-generated flash messages to english', function () {
    $business = $this->makeBusiness();
    $appointment = $this->createAppointment(
        $this->addStaff($business),
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_COMPLETED,
    );

    $this->withCookie('nobat_locale', 'en')
        ->from(route('businesses.show', $business->slug))
        ->post(route('reviews.store', $business->slug), [
            'code' => $appointment->code,
            'rating' => 5,
            'comment' => 'Great experience',
        ]);

    $this->withCookie('nobat_locale', 'en')
        ->get(route('bookings.show', $appointment->code))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('flash.success', 'Thanks for your review! It goes live once the business manager approves it.'));
});

it('keeps persian flash messages for default visitors', function () {
    $business = $this->makeBusiness();
    $appointment = $this->createAppointment(
        $this->addStaff($business),
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_COMPLETED,
    );

    $this->from(route('businesses.show', $business->slug))
        ->post(route('reviews.store', $business->slug), [
            'code' => $appointment->code,
            'rating' => 4,
            'comment' => 'زمان‌بندی دقیق بود',
        ]);

    $this->get(route('bookings.show', $appointment->code))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('flash.success', 'ممنون از نظر شما! پس از تأیید مدیر کسب‌وکار نمایش داده می‌شود.'));
});

it('keeps fa and en server translation files in sync', function () {
    $fa = json_decode((string) file_get_contents(lang_path('fa.json')), true, flags: JSON_THROW_ON_ERROR);
    $en = json_decode((string) file_get_contents(lang_path('en.json')), true, flags: JSON_THROW_ON_ERROR);

    expect(array_keys($fa))->toEqual(array_keys($en));
});

it('keeps fa and en frontend translation files in sync', function () {
    $fa = json_decode((string) file_get_contents(resource_path('js/locales/fa.json')), true, flags: JSON_THROW_ON_ERROR);
    $en = json_decode((string) file_get_contents(resource_path('js/locales/en.json')), true, flags: JSON_THROW_ON_ERROR);

    $flatten = function (array $items, string $prefix = '') use (&$flatten): array {
        $keys = [];

        foreach ($items as $key => $value) {
            $path = $prefix === '' ? (string) $key : "$prefix.$key";

            if (is_array($value)) {
                $keys = [...$keys, ...$flatten($value, $path)];
            } else {
                $keys[] = $path;
            }
        }

        return $keys;
    };

    expect($flatten($fa))->toEqual($flatten($en))
        ->and(count($flatten($fa)))->toBeGreaterThan(100)
        ->and(Str::length((string) json_encode($en)))->toBeGreaterThan(4000);
});

<?php

use App\Models\Appointment;
use App\Models\User;
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

    $this->withCookie('nobat_locale', 'ar')
        ->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('locale', 'ar'));
});

it('persists a locale choice and rejects unsupported locales', function () {
    $this->post(route('locale.switch'), ['locale' => 'en'])
        ->assertRedirect()
        ->assertCookie('nobat_locale', 'en');

    $this->post(route('locale.switch'), ['locale' => 'ar'])
        ->assertRedirect()
        ->assertCookie('nobat_locale', 'ar');

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

it('serves the arabic RTL shell when the cookie is set', function () {
    $this->withCookie('nobat_locale', 'ar')
        ->get(route('home'))
        ->assertOk()
        ->assertSee('lang="ar"', false)
        ->assertSee('dir="rtl"', false);
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

it('translates server-generated flash messages to arabic', function () {
    $business = $this->makeBusiness();
    $appointment = $this->createAppointment(
        $this->addStaff($business),
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_COMPLETED,
    );

    // The flash message is translated when the POST runs, so the arabic
    // cookie must ride that request — exactly like the english test above.
    $this->withCookie('nobat_locale', 'ar')
        ->from(route('businesses.show', $business->slug))
        ->post(route('reviews.store', $business->slug), [
            'code' => $appointment->code,
            'rating' => 5,
            'comment' => 'تجربة رائعة',
        ]);

    $this->withCookie('nobat_locale', 'ar')
        ->get(route('bookings.show', $appointment->code))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('flash.success', 'شكراً على تقييمك! سيظهر علناً بعد موافقة مدير النشاط التجاري.'));
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

it('translates the management dashboard flash messages to english', function () {
    $business = $this->makeBusiness();
    $manager = User::find($business->owner_id);

    $this->withCookie('nobat_locale', 'en')
        ->actingAs($manager)
        ->post(route('services.store'), [
            'name' => 'Dental check',
            'duration_minutes' => 30,
            'price' => 400_000,
            'is_active' => true,
            'sort_order' => 0,
        ])
        ->assertSessionHas('success', 'New service saved.');
});

it('keeps all three server translation files in sync', function () {
    $fa = json_decode((string) file_get_contents(lang_path('fa.json')), true, flags: JSON_THROW_ON_ERROR);
    $en = json_decode((string) file_get_contents(lang_path('en.json')), true, flags: JSON_THROW_ON_ERROR);
    $ar = json_decode((string) file_get_contents(lang_path('ar.json')), true, flags: JSON_THROW_ON_ERROR);

    expect(array_keys($fa))->toEqual(array_keys($en))
        ->and(array_keys($fa))->toEqual(array_keys($ar));
});

it('keeps all three frontend translation files in sync', function () {
    $fa = json_decode((string) file_get_contents(resource_path('js/locales/fa.json')), true, flags: JSON_THROW_ON_ERROR);
    $en = json_decode((string) file_get_contents(resource_path('js/locales/en.json')), true, flags: JSON_THROW_ON_ERROR);
    $ar = json_decode((string) file_get_contents(resource_path('js/locales/ar.json')), true, flags: JSON_THROW_ON_ERROR);

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
        ->and($flatten($fa))->toEqual($flatten($ar))
        ->and(count($flatten($fa)))->toBeGreaterThan(100)
        ->and(Str::length((string) json_encode($en)))->toBeGreaterThan(4000)
        ->and(Str::length((string) json_encode($ar)))->toBeGreaterThan(4000);
});

it('ships the english auth and validation catalogs in the same shape as fa and ar', function () {
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

    foreach (['auth', 'validation'] as $group) {
        $fa = $flatten(require lang_path("fa/$group.php"));
        $en = $flatten(require lang_path("en/$group.php"));
        $ar = $flatten(require lang_path("ar/$group.php"));

        expect($fa)->toEqual($en)
            ->and($fa)->toEqual($ar);
    }
});

it('answers english visitors with english auth failures, not persian ones', function () {
    $response = $this->withCookie('nobat_locale', 'en')
        ->from(route('login'))
        ->post(route('login'), ['email' => 'ghost@example.com', 'password' => 'not-the-password']);

    $response->assertSessionHasErrors('email');

    expect((string) session('errors')->first('email'))->toContain('do not match our records');
});

it('translates the confirmed, image and mimes rules for all three locales', function () {
    $expectations = [
        'fa' => ['confirmed' => 'مطابقت ندارد', 'mimes' => 'فرمت'],
        'ar' => ['confirmed' => 'غير متطابق', 'mimes' => 'من النوع'],
        'en' => ['confirmed' => 'confirmation does not match', 'mimes' => 'file of type'],
    ];

    foreach ($expectations as $locale => $needles) {
        app()->setLocale($locale);

        expect(__('validation.confirmed', ['attribute' => 'password']))->toContain($needles['confirmed'])
            ->and(__('validation.mimes', ['attribute' => 'photo', 'values' => 'jpg, png, webp']))->toContain($needles['mimes']);
    }

    app()->setLocale('fa');
});

it('renders the branded error pages in the visitor language', function () {
    config(['app.debug' => false]);

    $this->get('/no-such-page')
        ->assertStatus(404)
        ->assertSee('lang="fa"', false)
        ->assertSee('dir="rtl"', false)
        ->assertSee('صفحه‌ای که دنبالش بودید پیدا نشد', false);

    $this->withCookie('nobat_locale', 'en')
        ->get('/no-such-page')
        ->assertStatus(404)
        ->assertSee('lang="en"', false)
        ->assertSee('dir="ltr"', false)
        ->assertSee('The page you were looking for does not exist.', false)
        ->assertSee('Back to the homepage', false);

    $this->withCookie('nobat_locale', 'ar')
        ->get('/no-such-page')
        ->assertStatus(404)
        ->assertSee('lang="ar"', false)
        ->assertSee('dir="rtl"', false)
        ->assertSee('الصفحة التي كنت تبحث عنها غير موجودة', false)
        ->assertSee('العودة إلى الصفحة الرئيسية', false);
});

it('renders the printed invoice in the visitor language', function () {
    $business = $this->makeBusiness();
    $staff = $this->addStaff($business);
    $appointment = $this->createAppointment(
        $staff,
        $this->serviceOf($business),
        $this->nextOpenDate()->setTimeFromTimeString('10:00'),
        Appointment::STATUS_CONFIRMED,
    );

    $invoice = \App\Models\Invoice::query()->create([
        'number' => 'INV-TEST-1',
        'business_id' => $business->id,
        'appointment_id' => $appointment->id,
        'amount' => 500_000,
        'currency' => 'IRT',
        'status' => \App\Models\Invoice::STATUS_PAID,
        'snapshot' => ['customer_name' => 'مشتری آزمون', 'customer_phone' => '09120000000'],
        'issued_at' => now(),
        'paid_at' => now(),
    ]);

    $this->get(route('invoices.show', $invoice->number))
        ->assertOk()
        ->assertSee('lang="fa"', false)
        ->assertSee('فاکتور نوبت', false)
        ->assertSee('پرداخت‌شده', false);

    $this->withCookie('nobat_locale', 'en')
        ->get(route('invoices.show', $invoice->number))
        ->assertOk()
        ->assertSee('lang="en"', false)
        ->assertSee('dir="ltr"', false)
        ->assertSee('Nobat invoice', false)
        ->assertSee('Paid', false);
});

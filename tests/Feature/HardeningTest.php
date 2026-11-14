<?php

use App\Models\Invoice;
use Illuminate\Support\Facades\URL;

it('stamps the security header set on web responses', function () {
    $response = $this->get('/');

    $response->assertOk();
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    // the test env is not production — HSTS must stay off
    $response->assertHeaderMissing('Strict-Transport-Security');
});

it('keeps the headers on the branded error pages', function () {
    $response = $this->get('/this-route-does-not-exist');

    $response->assertNotFound();
    $response->assertSee('صفحه‌ای که دنبالش بودید پیدا نشد', false);
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('stamps the security header set on api responses', function () {
    $response = $this->getJson('/api/v1/me');

    $response->assertUnauthorized();
    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
});

it('ships a per-request CSP with a fresh nonce on the layout', function () {
    $first = $this->get('/');
    $first->assertOk();
    $first->assertHeaderMissing('Strict-Transport-Security'); // test env

    $csp = $first->headers->get('Content-Security-Policy');
    expect($csp)->not->toBeNull()
        ->and($csp)->toContain("default-src 'self'")
        ->and($csp)->toContain("script-src 'self' 'nonce-")
        ->and($csp)->toContain("'strict-dynamic'")
        ->and($csp)->toContain("object-src 'none'");

    // The nonce must rotate between requests — otherwise an attacker who
    // captures it once can mint trusted scripts forever.
    $second = $this->get('/');
    expect($second->headers->get('Content-Security-Policy'))->not->toBe($csp);
});

it('rejects a bare invoice URL without a signature', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $appointment = $this->createAppointment($this->addStaff($business), $service, $this->nextOpenDate()->setTimeFromTimeString('10:00'));

    $invoice = $appointment->invoice()->create([
        'number' => 'INV-NOCSP-TEST',
        'business_id' => $business->id,
        'amount' => $service->price,
        'currency' => 'IRT',
        'status' => Invoice::STATUS_ISSUED,
        'snapshot' => [
            'business' => ['name' => $business->name],
            'service' => ['name' => $service->name],
            'customer_name' => $appointment->customer_name,
            'customer_phone' => $appointment->customer_phone,
        ],
        'issued_at' => now(),
    ]);

    // A harvested URL without ?signature=&expires= must not leak the
    // customer's invoice — the controller now requires a valid signature.
    $this->get(route('invoices.show', $invoice->number))->assertForbidden();
});

it('accepts a signed invoice URL with a valid signature', function () {
    $business = $this->makeBusiness();
    $service = $this->serviceOf($business);
    $appointment = $this->createAppointment($this->addStaff($business), $service, $this->nextOpenDate()->setTimeFromTimeString('10:00'));

    $invoice = $appointment->invoice()->create([
        'number' => 'INV-SIGNED-TEST',
        'business_id' => $business->id,
        'amount' => $service->price,
        'currency' => 'IRT',
        'status' => Invoice::STATUS_ISSUED,
        'snapshot' => [
            'business' => ['name' => $business->name],
            'service' => ['name' => $service->name],
            'customer_name' => $appointment->customer_name,
            'customer_phone' => $appointment->customer_phone,
        ],
        'issued_at' => now(),
    ]);

    $url = URL::signedRoute('invoices.show', $invoice->number, now()->addDays(7));

    $this->get($url)->assertOk()->assertSee($invoice->number);
});

it('rate-limits the password-reset link endpoint', function () {
    // Five requests in the same minute, then a 429 on the sixth.
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('password.email'), ['email' => 'someone@example.com'])
            ->assertRedirect(); // redirect back with status, not 429
    }

    $this->post(route('password.email'), ['email' => 'someone@example.com'])
        ->assertTooManyRequests();
});

it('rate-limits the login endpoint', function () {
    // Login is the brute-force target; it now shares the same five-per-
    // minute wall as the reset endpoint. Five attempts (failing auth, but
    // passing validation and the throttle counter) then a 429 on the sixth.
    for ($i = 0; $i < 5; $i++) {
        $this->post(route('login'), [
            'email' => 'someone@example.com',
            'password' => 'not-the-right-password',
        ])->assertRedirect(); // back to login with errors, not 429
    }

    $this->post(route('login'), [
        'email' => 'someone@example.com',
        'password' => 'not-the-right-password',
    ])->assertTooManyRequests();
});

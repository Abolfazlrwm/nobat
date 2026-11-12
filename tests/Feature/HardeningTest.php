<?php

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

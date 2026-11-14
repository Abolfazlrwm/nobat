<?php

use App\Http\Middleware\PinClientIpFromProxyHeaders;
use Illuminate\Http\Request;

it('collapses X-Forwarded-For onto the Cloudflare visitor header', function () {
    $request = Request::create('/forgot-password', 'POST');
    $request->headers->set('X-Forwarded-For', '198.51.100.9, 10.0.0.1');
    $request->headers->set('CF-Connecting-IP', '203.0.113.7');

    (new PinClientIpFromProxyHeaders)->handle($request, fn ($r) => $r);

    expect($request->headers->get('X-Forwarded-For'))->toBe('203.0.113.7');
});

it('accepts IPv6 visitors in the Cloudflare header', function () {
    $request = Request::create('/forgot-password', 'POST');
    $request->headers->set('X-Forwarded-For', '198.51.100.9');
    $request->headers->set('CF-Connecting-IP', '2001:db8::5');

    (new PinClientIpFromProxyHeaders)->handle($request, fn ($r) => $r);

    expect($request->headers->get('X-Forwarded-For'))->toBe('2001:db8::5');
});

it('leaves requests without the Cloudflare header untouched', function () {
    $request = Request::create('/forgot-password', 'POST');
    $request->headers->set('X-Forwarded-For', '198.51.100.9, 10.0.0.1');

    (new PinClientIpFromProxyHeaders)->handle($request, fn ($r) => $r);

    expect($request->headers->get('X-Forwarded-For'))->toBe('198.51.100.9, 10.0.0.1');
});

it('ignores a malformed Cloudflare visitor header', function () {
    $request = Request::create('/forgot-password', 'POST');
    $request->headers->set('X-Forwarded-For', '198.51.100.9');
    $request->headers->set('CF-Connecting-IP', '<script>alert(1)</script>');

    (new PinClientIpFromProxyHeaders)->handle($request, fn ($r) => $r);

    expect($request->headers->get('X-Forwarded-For'))->toBe('198.51.100.9');
});

it('keeps one rate-limit bucket for a visitor behind churning proxy edges', function () {
    // The production failure mode, replayed: each request arrives with a
    // different edge address in X-Forwarded-For, but the same real visitor
    // in CF-Connecting-IP. Before the pinning middleware the five requests
    // landed in five separate buckets and the sixth sailed through; with
    // the fix the bucket keys on 203.0.113.7 and the sixth hits the wall.
    for ($i = 1; $i <= 5; $i++) {
        $this->post(route('password.email'), ['email' => 'visitor@example.com'], [
            'X-Forwarded-For' => "198.51.100.{$i}, 10.0.0.1",
            'CF-Connecting-IP' => '203.0.113.7',
        ])->assertRedirect(); // redirect back with status, not 429
    }

    $this->post(route('password.email'), ['email' => 'visitor@example.com'], [
        'X-Forwarded-For' => '198.51.100.99, 10.0.0.1',
        'CF-Connecting-IP' => '203.0.113.7',
    ])->assertTooManyRequests();
});

it('gives distinct visitors distinct buckets even when edges repeat', function () {
    // The inverse guarantee: pinning must not merge different people.
    $headers = fn (string $visitor) => [
        'X-Forwarded-For' => '198.51.100.1, 10.0.0.1', // same edge, always
        'CF-Connecting-IP' => $visitor,
    ];

    for ($i = 0; $i < 5; $i++) {
        $this->post(route('password.email'), ['email' => 'a@example.com'], $headers('203.0.113.7'))
            ->assertRedirect();
    }

    // The same edge address, a different visitor — still welcome.
    $this->post(route('password.email'), ['email' => 'b@example.com'], $headers('203.0.113.99'))
        ->assertRedirect();
});

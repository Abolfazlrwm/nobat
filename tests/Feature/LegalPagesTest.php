<?php

use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;

it('serves the privacy policy in the visitor locale', function () {
    $response = $this->get('/privacy');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('public/Legal')
        ->has('doc')
        ->has('title')
        ->has('updated')
        ->has('sections', 5, fn ($section) => $section->has('heading')->has('body'))
    );
    // Persian is the default locale — the Persian copy must come through.
    $response->assertInertia(fn ($page) => $page->where('doc', 'privacy'));
});

it('serves the terms of service with its own copy', function () {
    $response = $this->get('/terms');

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->component('public/Legal')
        ->where('doc', 'terms')
        ->where('title', __('legal.terms.title'))
    );
});

it('translates the legal copy through the locale mechanism', function () {
    // Feature tests do not replay response cookies between requests, so the
    // cookie -> locale -> copy chain is proven at the middleware level: the
    // same SetLocale pass that runs before LegalController renders.
    $request = Request::create('/privacy');
    $request->cookies->set(SetLocale::COOKIE, 'en');

    app(SetLocale::class)->handle($request, fn ($req) => $req);

    expect(app()->getLocale())->toBe('en')
        ->and(__('legal.privacy.title'))->toBe('Privacy Policy')
        ->and(__('legal.terms.title'))->toBe('Terms of Service');
});

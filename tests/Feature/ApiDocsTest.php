<?php

use App\Support\ApiSpec;
use Illuminate\Routing\Route as RouteObject;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia;

/**
 * Normalise a URI so parameter NAMES never break the comparison: the spec
 * says {id}, the route registration says {apiToken} — both are the same
 * contract point.
 */
function normalizeApiPath(string $uri): string
{
    return '/'.preg_replace('/\{[^}]+\}/', '{param}', trim($uri, '/'));
}

/** @return array<string, string> method => normalized path for every api/ route */
function registeredApiOperations(): array
{
    return collect(app('router')->getRoutes())
        ->filter(fn (RouteObject $route) => Str::startsWith($route->uri(), 'api/'))
        ->mapWithKeys(fn (RouteObject $route) => [
            strtoupper($route->methods()[0]).' '.normalizeApiPath($route->uri()) => normalizeApiPath($route->uri()),
        ])
        ->all();
}

/** @return array<string, string> method => normalized path from the OpenAPI spec */
function documentedApiOperations(): array
{
    $operations = [];

    foreach (ApiSpec::toArray()['paths'] as $path => $pathItem) {
        foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
            if (isset($pathItem[$method])) {
                $operations[strtoupper($method).' '.normalizeApiPath($path)] = normalizeApiPath($path);
            }
        }
    }

    return $operations;
}

it('serves the human reference page from the spec', function () {
    $this->get(route('docs.page'))
        ->assertOk()
        ->assertInertia(fn (AssertableInertia $page) => $page
            ->component('public/ApiDocs')
            ->has('spec.paths', 5)
            ->has('spec.servers.0.url'));
});

it('serves the raw machine-readable contract', function () {
    $response = $this->get(route('docs.spec'));

    $response->assertOk()
        ->assertHeader('Content-Disposition', 'inline; filename="openapi.json"');

    $spec = $response->json();

    expect($spec['openapi'])->toBe('3.1.0')
        ->and($spec['info']['version'])->toBe((string) config('app.version'))
        ->and($spec['servers'][0]['url'])->toBe(rtrim(config('app.url'), '/'))
        ->and($spec['components']['securitySchemes']['bearerAuth']['scheme'])->toBe('bearer');
});

it('never drifts from the routes — the spec documents exactly what exists', function () {
    // Strict comparison also compares order, which carries no contract
    // meaning here — sort both sides first.
    $documented = documentedApiOperations();
    $registered = registeredApiOperations();
    ksort($documented);
    ksort($registered);

    expect($documented)->toBe($registered);
});

it('leaves token creation open and protects everything else', function () {
    $spec = ApiSpec::toArray();

    expect($spec['paths']['/api/v1/tokens']['post']['security'])->toBe([])
        ->and($spec['paths']['/api/v1/me']['get'])->not->toHaveKey('security')
        ->and($spec['security'])->toBe([['bearerAuth' => []]]);
});

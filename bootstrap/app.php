<?php

use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\EnsureApiToken;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Behind PaaS TLS terminators (Render, Railway, ...) artisan serve
        // sees plain HTTP — trust forwarded headers so URLs come out https.
        $middleware->trustProxies(at: '*');

        $middleware->web(append: [
            SetLocale::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            AddSecurityHeaders::class,
        ]);

        $middleware->api(append: [
            AddSecurityHeaders::class,
        ]);

        $middleware->alias([
            'api-token' => EnsureApiToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Error pages and aborted JSON bypass route middleware — stamp the
        // security headers (with a fresh CSP nonce, since this responder
        // does not see the per-request attributes set by AddSecurityHeaders)
        // onto those responses as well.
        $exceptions->respond(function (Response $response) {
            return AddSecurityHeaders::apply($response, bin2hex(random_bytes(16)));
        });
    })->create();

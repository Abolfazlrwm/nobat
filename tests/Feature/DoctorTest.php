<?php

use Illuminate\Support\Facades\Cache;

it('runs the full preflight and exits green on a healthy install', function () {
    Cache::put('nobat:health:scheduler-heartbeat', now()->timestamp, 300);

    $this->artisan('nobat:doctor')->assertSuccessful();
});

it('names every check in its report table', function () {
    $this->artisan('nobat:doctor')->assertSuccessful()
        ->expectsOutputToContain('database')
        ->expectsOutputToContain('storage')
        ->expectsOutputToContain('cache')
        ->expectsOutputToContain('queue')
        ->expectsOutputToContain('sms driver')
        ->expectsOutputToContain('payment driver')
        ->expectsOutputToContain('APP_KEY');
});

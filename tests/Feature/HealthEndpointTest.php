<?php

use Illuminate\Support\Facades\Cache;

it('answers /health with full product diagnostics', function () {
    $response = $this->get('/health');

    $response->assertOk();
    $response->assertJsonStructure([
        'status',
        'database',
        'storage_writable',
        'scheduler' => ['healthy', 'last_sweep_ago_seconds'],
        'queue' => ['connection'],
        'time',
    ]);
    $response->assertJsonPath('database', true);
    $response->assertJsonPath('status', 'ok');
});

it('reports a dead scheduler as unhealthy before any sweep has run', function () {
    Cache::forget('nobat:health:scheduler-heartbeat');

    $response = $this->get('/health');

    $response->assertOk(); // scheduler death degrades the report, not the instance
    $response->assertJsonPath('scheduler.healthy', false);
    $response->assertJsonPath('scheduler.last_sweep_ago_seconds', null);
});

it('reads the scheduler heartbeat the sweep stamps on every pass', function () {
    $this->artisan('nobat:release-expired-holds')->assertSuccessful();

    $response = $this->get('/health');

    $response->assertOk();
    $response->assertJsonPath('scheduler.healthy', true);
    $lag = $response->json('scheduler.last_sweep_ago_seconds');
    expect($lag)->toBeInt()->toBeLessThanOrEqual(5);
});

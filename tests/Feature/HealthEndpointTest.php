<?php

use App\Jobs\SendOtpSms;
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

it('reports queue depth, failed jobs, version and disk on the database queue', function () {
    config()->set('queue.default', 'database');

    SendOtpSms::dispatch('09120000000', '12345');

    $response = $this->get('/health');

    $response->assertOk();
    $response->assertJsonPath('queue.connection', 'database');
    $response->assertJsonPath('queue.depth', 1);
    $response->assertJsonPath('queue.failed', 0);
    expect($response->json('queue.oldest_pending_seconds'))->toBeInt()->toBeGreaterThanOrEqual(0)
        ->and($response->json('version'))->toBe(config('app.version'))
        ->and($response->json('disk_free_mb'))->toBeInt()->toBeGreaterThan(0);
});

it('leaves the queue metrics null when the connection runs inline', function () {
    config()->set('queue.default', 'sync');

    $response = $this->get('/health');

    $response->assertOk();
    $response->assertJsonPath('queue.connection', 'sync');
    $response->assertJsonPath('queue.depth', null);
    $response->assertJsonPath('queue.failed', null);
});

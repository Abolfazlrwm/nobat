<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

/**
 * The scheduler contract the docs promise, asserted through the real
 * `schedule:list` path (the same one schedule:work uses in production).
 * Exact occurrence counts guard against double registration — adding a
 * second registration site silently doubles every sweep.
 */
it('registers every nobat maintenance command with the documented cadence, exactly once', function () {
    Artisan::call('schedule:list');
    $output = Artisan::output();

    expect(substr_count($output, 'nobat:release-expired-holds'))->toBe(1)
        ->and(substr_count($output, 'nobat:send-reminders'))->toBe(1)
        ->and(substr_count($output, 'nobat:prune-login-otps'))->toBe(1)
        ->and(substr_count($output, 'nobat:backup'))->toBe(1)
        ->and($output)->toContain('* * * * *')
        ->and($output)->toContain('0 * * * *')
        ->and($output)->toContain('0 0 * * *');
});

it('stamps the scheduler heartbeat when the hold sweeper runs', function () {
    Cache::forget('nobat:health:scheduler-heartbeat');

    $this->artisan('nobat:release-expired-holds')->assertSuccessful();

    expect(Cache::get('nobat:health:scheduler-heartbeat'))->not->toBeNull();
});

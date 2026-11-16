<?php

use App\Jobs\SendOtpSms;

it('parks dispatched jobs on the database queue when the connection is database', function () {
    config()->set('queue.default', 'database');

    SendOtpSms::dispatch('09120000000', '12345');

    $this->assertDatabaseCount('jobs', 1);
});

it('runs everything inline on sync — the local default needs no worker', function () {
    config()->set('queue.default', 'sync');

    SendOtpSms::dispatch('09120000000', '12345');

    $this->assertDatabaseCount('jobs', 0);
});

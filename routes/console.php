<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('nobat:release-expired-holds')->everyMinute();

Schedule::command('nobat:send-reminders')->hourly();

Schedule::command('nobat:prune-login-otps')->daily();

// SQLite installs (the demo) keep a consistent daily backup with retention;
// on the MySQL production path this command no-ops by design.
Schedule::command('nobat:backup')->daily();

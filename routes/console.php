<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('nobat:release-expired-holds')->everyMinute();

Schedule::command('nobat:send-reminders')->hourly();

<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('nobat:release-expired-holds')->everyMinute();

Schedule::command('nobat:send-reminders')->hourly();

Schedule::command('nobat:prune-login-otps')->daily();

// SQLite installs (the demo) keep a consistent daily backup: retention comes
// from config (NOBAT_BACKUP_KEEP) and each snapshot is copied to
// NOBAT_BACKUP_DISK when one is configured. On the MySQL production path this
// command no-ops by design — use mysqldump there (docs/OPERATIONS.md §3).
Schedule::command('nobat:backup')->daily();

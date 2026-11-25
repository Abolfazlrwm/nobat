#!/usr/bin/env bash
# ---------------------------------------------------------------------------
# E2E boot script — used by playwright.config.ts as its webServer command.
#
# Writes the demo-friendly settings into .env (sed-set, so existing lines
# are REPLACED — phpdotenv keeps the first occurrence, appending would not
# override) and then boots a freshly seeded server. The E2E suite owns the
# install it runs against, so mutating .env here is safe; on CI the file
# was just copied from .env.example anyway.
#   NOBAT_DEMO=true         one-click demo accounts on the login page
#   NOBAT_SMS_DRIVER=log    OTP codes land in storage/logs/laravel.log
#   QUEUE_CONNECTION=sync   the SMS job runs inline, log is written at once
# PHP_BIN overrides the php binary (static builds without PATH php).
# ---------------------------------------------------------------------------
set -euo pipefail

cd "$(dirname "$0")/.."

PHP_BIN="${PHP_BIN:-php}"

for kv in NOBAT_DEMO=true NOBAT_SMS_DRIVER=log QUEUE_CONNECTION=sync; do
    key="${kv%%=*}"
    val="${kv#*=}"
    if grep -q "^${key}=" .env; then
        sed -i.bak "s|^${key}=.*|${key}=${val}|" .env && rm -f .env.bak
    else
        printf '%s=%s\n' "$key" "$val" >> .env
    fi
done

"$PHP_BIN" artisan migrate:fresh --seed --force

echo ">> E2E server booting on http://127.0.0.1:8080"
exec "$PHP_BIN" artisan serve --host=127.0.0.1 --port=8080

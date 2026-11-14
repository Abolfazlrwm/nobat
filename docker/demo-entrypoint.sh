#!/bin/sh
# ---------------------------------------------------------------------------
# Nobat — demo boot script
# Prepares a throwaway SQLite database and serves the app on $PORT.
# Designed for free PaaS hosts (Render, Railway, Koyeb, ...).
# ---------------------------------------------------------------------------
set -e

# APP_KEY: generate a fresh one per boot when not provided.
# Sessions/encrypted cookies reset on every redeploy — acceptable for a demo.
if [ -z "${APP_KEY:-}" ]; then
    export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    echo ">> APP_KEY generated for this boot"
fi

# APP_URL: Render injects RENDER_EXTERNAL_URL automatically.
if [ -z "${APP_URL:-}" ]; then
    if [ -n "${RENDER_EXTERNAL_URL:-}" ]; then
        # Render always serves https externally. Normalise the scheme so
        # Laravel does not emit http:// asset/route links — browsers block
        # mixed content on https pages and the app renders blank.
        export APP_URL="$(printf '%s' "$RENDER_EXTERNAL_URL" | sed 's|^http://|https://|')"
    else
        export APP_URL="http://localhost:${PORT:-8080}"
    fi
fi

# Show the one-click demo accounts on the login page.
export NOBAT_DEMO="${NOBAT_DEMO:-true}"

echo ">> preparing sqlite database"
mkdir -p database
touch database/database.sqlite

echo ">> running migrations"
php artisan migrate --force

echo ">> seeding demo data (pars-clinic, aftab-salon, demo users)"
php artisan db:seed --force

php artisan storage:link >/dev/null 2>&1 || true

# Cache the framework artifacts — a cold free-tier container serves its
# first page noticeably faster with config/routes/views precompiled.
echo ">> caching config, routes and views"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# The scheduler must run somewhere: ReleaseExpiredHolds frees unpaid
# bookings every minute and SendReminders queues reminder SMS hourly.
# In this single-container demo the web container hosts it next to the
# web server; a real deployment gives it a dedicated worker or cron —
# see docs/OPERATIONS.md.
if [ "${NOBAT_SCHEDULER:-true}" != "false" ]; then
    echo ">> starting the scheduler in the background"
    nohup php artisan schedule:work >> storage/logs/scheduler.log 2>&1 &
fi

echo ">> serving on http://0.0.0.0:${PORT:-8080}"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"

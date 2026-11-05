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
    export APP_URL="${RENDER_EXTERNAL_URL:-http://localhost:${PORT:-8080}}"
fi

echo ">> preparing sqlite database"
mkdir -p database
touch database/database.sqlite

echo ">> running migrations"
php artisan migrate --force

echo ">> seeding demo data (pars-clinic, aftab-salon, demo users)"
php artisan db:seed --force

php artisan storage:link >/dev/null 2>&1 || true

echo ">> serving on http://0.0.0.0:${PORT:-8080}"
exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"

import { defineConfig } from '@playwright/test';

/**
 * E2E smoke suite — a real browser against the real Laravel server.
 *
 * The webServer boots a freshly migrated + seeded SQLite install on :8080
 * and /health is used as the readiness probe. Auth flows need these env
 * vars (export them before `npm run test:e2e`, the CI e2e job does too):
 *   NOBAT_DEMO=true          — shows the one-click demo accounts
 *   NOBAT_SMS_DRIVER=log     — OTP codes land in storage/logs/laravel.log
 *   QUEUE_CONNECTION=sync    — the SMS job runs inline, log is written at once
 */
export default defineConfig({
    testDir: './e2e',
    timeout: 30_000,
    // One worker on purpose: a shared SQLite DB, a shared log file and the
    // per-phone OTP rate limiter all assume the specs run sequentially.
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    reporter: process.env.CI ? [['list'], ['html', { open: 'never' }]] : [['list']],
    use: {
        baseURL: 'http://127.0.0.1:8080',
        trace: 'retain-on-failure',
    },
    webServer: {
        // e2e/serve.sh seeds a fresh install, writes the demo env into .env
        // (process env does not survive artisan serve's child on some PHP
        // builds) and boots on :8080. PHP_BIN overrides the php binary.
        command: 'bash e2e/serve.sh',
        url: 'http://127.0.0.1:8080/health',
        reuseExistingServer: !process.env.CI,
        timeout: 120_000,
    },
});

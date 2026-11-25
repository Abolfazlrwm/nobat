import { readFileSync } from 'node:fs';

import { test } from '@playwright/test';

/**
 * Auth flows: the one-click demo accounts (email + the seeded 'password')
 * and the real OTP path — a code is minted, the SMS job runs inline (log
 * driver) and the test fishes the code out of the log like a dev would.
 */
const LOG = 'storage/logs/laravel.log';

const PERSIAN_DIGITS: Record<string, string> = {
    '۰': '0',
    '۱': '1',
    '۲': '2',
    '۳': '3',
    '۴': '4',
    '۵': '5',
    '۶': '6',
    '۷': '7',
    '۸': '8',
    '۹': '9',
};

function latestOtpCode(phone: string): string {
    const lines = readFileSync(LOG, 'utf8')
        .split('\n')
        .filter((line) => line.includes('SMS sent'))
        .filter((line) => line.includes(phone) || !line.includes('"to"'));
    const line = lines.at(-1);

    if (!line) {
        throw new Error(`no OTP SMS for ${phone} found in ${LOG}`);
    }

    // Scan the message JSON field only — a whole-line match would grab the
    // first six digits of the phone number itself, not the minted code.
    const message = line.match(/"message":"([^"]*)"/)?.[1] ?? '';
    const raw = message.match(/[0-9۰-۹]{6}/)?.[0];

    if (!raw) {
        throw new Error(`no OTP code for ${phone} found in ${LOG}`);
    }

    return raw.replace(/[۰-۹]/g, (digit) => PERSIAN_DIGITS[digit] ?? digit);
}

test.describe('auth', () => {
    test('one-click demo login reaches the manager dashboard', async ({ page }) => {
        await page.goto('/login');

        await page.getByRole('button', { name: /مدیر کسب‌وکار/ }).click();

        await page.waitForURL(/\/dashboard/, { timeout: 15_000 });
    });

    test('the OTP flow sends a real SMS job and signs in', async ({ page }) => {
        await page.goto('/otp-login');

        await page.fill('#phone', '09121234567');
        await page.locator('form button[type="submit"]').click();

        // The code step only renders once the POST — and the inline SMS job
        // with it — has finished, so this run's log line is on disk by now.
        await page.locator('#code').waitFor({ state: 'visible', timeout: 10_000 });
        await page.fill('#code', latestOtpCode('09121234567'));
        await page.locator('form button[type="submit"]').click();

        // Customers sign in to their own bookings ledger, not the staff dashboard.
        await page.waitForURL(/\/my\/appointments/, { timeout: 15_000 });
    });
});

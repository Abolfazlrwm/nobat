import { expect, test } from '@playwright/test';

/**
 * The public surface a first-time visitor sees, asserted like a guest
 * would: the health endpoint, the Persian landing, a real seeded business
 * page and the language switcher.
 */
test.describe('public pages', () => {
    test('health endpoint reports ok and a semver version', async ({ request }) => {
        const res = await request.get('/health');
        const body = await res.json();

        expect(res.ok()).toBeTruthy();
        expect(body.status).toBe('ok');
        expect(body.database).toBe(true);
        // Semver-shaped but never pinned — the suite must outlive releases.
        expect(String(body.version)).toMatch(/^\d+\.\d+\.\d+$/);
    });

    test('the landing renders in Persian with the brand', async ({ page }) => {
        await page.goto('/');

        await expect(page.locator('html')).toHaveAttribute('lang', 'fa');
        await expect(page.getByText('نوبت', { exact: false }).first()).toBeVisible();
    });

    test('a seeded business page shows the clinic and its staff', async ({ page }) => {
        await page.goto('/b/pars-clinic');

        await expect(page.getByText('کلینیک سلامت پارس', { exact: false }).first()).toBeVisible();
        await expect(page.getByText('دکتر سارا محمدی', { exact: false }).first()).toBeVisible();
    });

    test('the language switcher flips the whole shell to English', async ({ page }) => {
        await page.goto('/');

        await page.getByRole('button', { name: 'تغییر زبان' }).click();
        await page.getByRole('menuitem', { name: 'English' }).click();

        await expect(page.locator('html')).toHaveAttribute('lang', 'en', { timeout: 10_000 });
    });
});

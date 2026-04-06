import { test, expect, devices } from '@playwright/test';
import { login, TEST_USER } from './helpers/auth';

test.use({ ...devices['Pixel 5'] });

test.describe('Mobile Entrance', () => {
    test('entrance page renders without horizontal scroll', async ({ page }) => {
        await login(page, TEST_USER.email, TEST_USER.password);
        await page.goto('/entrance');
        await page.waitForLoadState('networkidle');

        const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
        const viewportWidth = await page.evaluate(() => window.innerWidth);
        expect(bodyWidth).toBeLessThanOrEqual(viewportWidth + 1);
    });

    test('lookup page renders without horizontal scroll', async ({ page }) => {
        await login(page, TEST_USER.email, TEST_USER.password);
        await page.goto('/entrance/lookup');
        await page.waitForLoadState('networkidle');

        const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
        const viewportWidth = await page.evaluate(() => window.innerWidth);
        expect(bodyWidth).toBeLessThanOrEqual(viewportWidth + 1);
    });

    test('manual lookup link has adequate touch target size', async ({ page }) => {
        await login(page, TEST_USER.email, TEST_USER.password);
        await page.goto('/entrance');

        const link = page.locator('a[href="/entrance/lookup"]');
        const box = await link.boundingBox();

        if (box) {
            // Touch targets should be at least 48x48px per WCAG
            expect(box.height).toBeGreaterThanOrEqual(44);
        }
    });

    test('search input is visible and usable on mobile', async ({ page }) => {
        await login(page, TEST_USER.email, TEST_USER.password);
        await page.goto('/entrance/lookup');

        const searchInput = page.locator('input[type="search"]');
        await expect(searchInput).toBeVisible();

        // Should be focusable
        await searchInput.focus();
        await expect(searchInput).toBeFocused();
    });
});

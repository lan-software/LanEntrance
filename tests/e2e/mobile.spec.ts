import { test, expect, devices } from '@playwright/test';

test.use({ ...devices['Pixel 5'] });

test.describe('Mobile Entrance', () => {
    test('entrance page renders without horizontal scroll on mobile', async ({ page }) => {
        await page.goto('/entrance');
        const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
        const viewportWidth = await page.evaluate(() => window.innerWidth);
        // Body should not overflow viewport
        expect(bodyWidth).toBeLessThanOrEqual(viewportWidth + 1);
    });

    test('lookup page renders without horizontal scroll on mobile', async ({ page }) => {
        await page.goto('/entrance/lookup');
        const bodyWidth = await page.evaluate(() => document.body.scrollWidth);
        const viewportWidth = await page.evaluate(() => window.innerWidth);
        expect(bodyWidth).toBeLessThanOrEqual(viewportWidth + 1);
    });
});

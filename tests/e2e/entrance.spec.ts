import { test, expect } from '@playwright/test';
import { login, TEST_USER } from './helpers/auth';

test.describe('Entrance Scanner', () => {
    test('redirects unauthenticated users to login', async ({ page }) => {
        await page.goto('/entrance');
        await expect(page).toHaveURL(/login/);
    });

    test('loads scanner page when authenticated', async ({ page }) => {
        await login(page, TEST_USER.email, TEST_USER.password);
        await page.goto('/entrance');

        await expect(page).toHaveURL(/entrance/);
        await expect(page).toHaveTitle(/Entrance Scanner/);
    });

    test('shows manual lookup link on scanner page', async ({ page }) => {
        await login(page, TEST_USER.email, TEST_USER.password);
        await page.goto('/entrance');

        const lookupLink = page.locator('a[href="/entrance/lookup"]');
        await expect(lookupLink).toBeVisible();
        await expect(lookupLink).toContainText('Manual Lookup');
    });

    test('shows entrance link in sidebar navigation', async ({ page }) => {
        await login(page, TEST_USER.email, TEST_USER.password);
        await page.goto('/dashboard');

        const entranceNav = page.locator('a:has-text("Entrance")');
        await expect(entranceNav).toBeVisible();
    });
});

test.describe('Entrance Lookup', () => {
    test('redirects unauthenticated users to login', async ({ page }) => {
        await page.goto('/entrance/lookup');
        await expect(page).toHaveURL(/login/);
    });

    test('loads lookup page when authenticated', async ({ page }) => {
        await login(page, TEST_USER.email, TEST_USER.password);
        await page.goto('/entrance/lookup');

        await expect(page).toHaveURL(/entrance\/lookup/);
        await expect(page).toHaveTitle(/Manual Lookup/);
    });

    test('shows search input on lookup page', async ({ page }) => {
        await login(page, TEST_USER.email, TEST_USER.password);
        await page.goto('/entrance/lookup');

        const searchInput = page.locator('input[type="search"]');
        await expect(searchInput).toBeVisible();
    });

    test('shows back to scanner link on lookup page', async ({ page }) => {
        await login(page, TEST_USER.email, TEST_USER.password);
        await page.goto('/entrance/lookup');

        const scannerLink = page.locator('a[href="/entrance"]');
        await expect(scannerLink).toBeVisible();
        await expect(scannerLink).toContainText('Back to Scanner');
    });
});

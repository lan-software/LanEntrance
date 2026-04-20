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

        // toHaveTitle is flaky under Playwright here — Inertia's <Head> is
        // applied after client-side hydration and the CI pipeline sometimes
        // lands on the page before the title updates. Prefer a DOM-content
        // probe that proves the Scanner page rendered.
        await expect(page).toHaveURL(/entrance/);
        await expect(
            page.getByRole('link', { name: /Manual Lookup/ }),
        ).toBeVisible();
    });

    test('shows manual lookup link on scanner page', async ({ page }) => {
        await login(page, TEST_USER.email, TEST_USER.password);
        await page.goto('/entrance');

        // The sidebar also links to /entrance/lookup; scope to the in-page
        // call-to-action that has the "Manual Lookup" label.
        const lookupLink = page.getByRole('link', { name: /Manual Lookup/ });
        await expect(lookupLink).toBeVisible();
    });

    test('shows scanner link in sidebar navigation', async ({ page, isMobile }) => {
        // On mobile the sidebar is hidden behind a toggle; this test covers
        // the desktop layout only.
        test.skip(isMobile, 'sidebar is collapsed on mobile viewports');

        await login(page, TEST_USER.email, TEST_USER.password);
        await page.goto('/dashboard');

        // Sidebar exposes Scanner + Lookup entries to all authenticated users.
        await expect(
            page.getByRole('link', { name: /Scanner/ }),
        ).toBeVisible();
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

        // Prefer a DOM probe over toHaveTitle (see Scanner test above).
        await expect(page).toHaveURL(/entrance\/lookup/);
        await expect(
            page.getByRole('link', { name: /Back to Scanner/ }),
        ).toBeVisible();
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

        // Scope to the in-page CTA; the sidebar also links to /entrance.
        const scannerLink = page.getByRole('link', { name: /Back to Scanner/ });
        await expect(scannerLink).toBeVisible();
    });
});

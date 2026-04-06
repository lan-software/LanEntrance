import type { Page } from '@playwright/test';

/**
 * Login via the login form using local credentials.
 * Assumes LANCORE_ENABLED=false in test environment so local auth is available.
 */
export async function login(page: Page, email = 'test@example.com', password = 'password') {
    await page.goto('/login');
    await page.fill('input[name="email"], input[type="email"]', email);
    await page.fill('input[name="password"], input[type="password"]', password);
    await page.click('button[type="submit"]');
    await page.waitForURL(/dashboard|entrance/);
}

/**
 * Seed a test user via artisan tinker executed before tests.
 * Returns the user credentials.
 */
export const TEST_USER = {
    name: 'E2E Test User',
    email: 'e2e-test@lanentrance.test',
    password: 'password',
};

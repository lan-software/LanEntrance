import { execSync } from 'child_process';

/**
 * Seed test users before Playwright E2E tests run.
 * Requires the Laravel app to be running with a test database.
 */
export default function globalSetup() {
    const appUrl = process.env.APP_URL ?? 'http://localhost';

    try {
        // Seed a regular user for entrance tests
        execSync(
            `php artisan tinker --execute="\\App\\Models\\User::factory()->create(['email' => 'e2e-test@lanentrance.test', 'password' => bcrypt('password'), 'name' => 'E2E Test User', 'role' => 'user', 'lancore_user_id' => 9999]);"`,
            { stdio: 'pipe', timeout: 10000 },
        );

        // Seed a moderator user for override tests
        execSync(
            `php artisan tinker --execute="\\App\\Models\\User::factory()->create(['email' => 'e2e-moderator@lanentrance.test', 'password' => bcrypt('password'), 'name' => 'E2E Moderator', 'role' => 'moderator', 'lancore_user_id' => 9998]);"`,
            { stdio: 'pipe', timeout: 10000 },
        );
    } catch {
        // Users may already exist from a previous run — that's fine
    }

    console.log(`E2E global setup complete (app: ${appUrl})`);
}

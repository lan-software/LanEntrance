<?php

use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Load a LanCore API response fixture by name.
 *
 * @return array<string, mixed>
 */
function lancoreFixture(string $name): array
{
    $path = __DIR__.'/Fixtures/LanCore/'.$name.'.json';

    if (! file_exists($path)) {
        throw new RuntimeException("LanCore fixture not found: {$name}");
    }

    return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
}

/**
 * Generate HMAC-signed webhook headers for LanCore role sync tests.
 *
 * @return array<string, string>
 */
function lanEntranceRolesWebhookHeaders(string $body, string $secret): array
{
    return [
        'X-Webhook-Signature' => 'sha256='.hash_hmac('sha256', $body, $secret),
        'X-Webhook-Event' => 'user.roles_updated',
        'Content-Type' => 'application/json',
    ];
}

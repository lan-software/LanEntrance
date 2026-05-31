<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config([
        'lancore.enabled' => true,
        'lancore.internal_url' => null,
        'lancore.base_url' => 'http://lancore.test',
        'lancore.token' => 'lci_test_token',
        'lancore.app_slug' => 'lanentrance',
    ]);
});

it('falls back to the local login page when LanCore SSO is disabled', function () {
    config(['lancore.enabled' => false]);

    $this->get(route('auth.redirect'))
        ->assertRedirect(route('login', ['local' => 1]));
});

it('rejects a callback whose code is not 64 characters', function () {
    $this->get(route('auth.callback', ['code' => 'too-short']))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error', 'Invalid SSO callback. Please try again.');

    expect(User::query()->count())->toBe(0);
});

it('shows an expired-link message when the exchange returns a 400', function () {
    Http::fake(['*/api/integration/sso/exchange' => Http::response(['error' => 'expired'], 400)]);

    $this->get(route('auth.callback', ['code' => str_repeat('a', 64)]))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error', 'The login link has expired. Please try again.');

    $this->assertGuest();
});

it('shows a connection error for a non-400 exchange failure', function () {
    Http::fake(['*/api/integration/sso/exchange' => Http::response(['error' => 'forbidden'], 403)]);

    $this->get(route('auth.callback', ['code' => str_repeat('a', 64)]))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error', 'Could not connect to authentication service. Please try again later.');

    $this->assertGuest();
});

it('shows a connection error when LanCore is unavailable', function () {
    Http::fake(['*/api/integration/sso/exchange' => Http::response(['error' => 'boom'], 500)]);

    $this->get(route('auth.callback', ['code' => str_repeat('a', 64)]))
        ->assertRedirect(route('home'))
        ->assertSessionHas('error', 'Could not connect to authentication service. Please try again later.');

    $this->assertGuest();
});

it('reports SSO status as enabled', function () {
    config(['lancore.enabled' => true]);

    $this->getJson(route('auth.status'))
        ->assertOk()
        ->assertExactJson(['enabled' => true]);
});

it('reports SSO status as disabled', function () {
    config(['lancore.enabled' => false]);

    $this->getJson(route('auth.status'))
        ->assertOk()
        ->assertExactJson(['enabled' => false]);
});

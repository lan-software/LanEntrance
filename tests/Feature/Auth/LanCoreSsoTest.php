<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    config([
        'lancore.enabled' => true,
        'lancore.internal_url' => null,
        'lancore.base_url' => 'http://lancore.test',
        'lancore.token' => 'lci_test_token',
        'lancore.app_slug' => 'lanentrance',
    ]);
});

it('redirects to LanCore SSO when enabled', function () {
    $this->get(route('auth.redirect'))
        ->assertRedirectContains('lancore.test/sso/authorize');
});

it('creates a local user from a valid LanCore callback', function () {
    Http::fake([
        '*/api/integration/sso/exchange' => Http::response([
            'data' => [
                'id' => 42,
                'username' => 'door-admin',
                'email' => 'door-admin@example.com',
                'roles' => ['moderator'],
            ],
        ]),
    ]);

    $this->get(route('auth.callback', ['code' => str_repeat('a', 64)]))
        ->assertRedirect(route('dashboard'));

    $user = User::query()->where('lancore_user_id', 42)->first();

    expect($user)->not->toBeNull()
        ->and($user?->role)->toBe(UserRole::Moderator);
});

it('redirects the login page to LanCore when enabled', function () {
    $this->get(route('login'))
        ->assertRedirect(route('auth.redirect'));
});
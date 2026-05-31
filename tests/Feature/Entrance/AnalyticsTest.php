<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    config()->set('lancore.enabled', true);
    config()->set('lancore.base_url', 'http://lancore.test');
    config()->set('lancore.internal_url', 'http://lancore.test');
    config()->set('lancore.token', 'lci_test_token');
});

it('renders analytics stats for an admin', function () {
    Http::fake(['*/api/entrance/stats*' => Http::response([
        'checked_in' => 120,
        'total' => 200,
    ])]);

    $admin = User::factory()->lanCoreUser()->withRole(UserRole::Admin)->create();

    $this->actingAs($admin)
        ->get(route('entrance.analytics'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('entrance/Analytics')
            ->where('stats.checked_in', 120)
            ->where('stats.total', 200));
});

it('renders an error payload when LanCore is unreachable', function () {
    Http::fake(['*/api/entrance/stats*' => fn () => throw new ConnectionException('refused')]);

    $admin = User::factory()->lanCoreUser()->withRole(UserRole::Admin)->create();

    $this->actingAs($admin)
        ->get(route('entrance.analytics'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('entrance/Analytics')
            ->where('stats.error', true)
            ->where('stats.message', 'Unable to load analytics — LanCore is unreachable.'));
});

it('forwards the selected event id to the stats endpoint', function () {
    Http::fake(['*/api/entrance/stats*' => Http::response(['checked_in' => 1])]);

    $admin = User::factory()->lanCoreUser()->withRole(UserRole::Admin)->create();

    $this->actingAs($admin)
        ->withSession(['entrance_event_id' => 5])
        ->get(route('entrance.analytics'))
        ->assertOk();

    Http::assertSent(fn ($req) => str_contains($req->url(), '/api/entrance/stats')
        && str_contains($req->url(), 'event_id=5'));
});

it('forbids non-admin users from viewing analytics', function () {
    $moderator = User::factory()->lanCoreUser()->withRole(UserRole::Moderator)->create();

    $this->actingAs($moderator)
        ->get(route('entrance.analytics'))
        ->assertForbidden();
});

it('requires authentication to view analytics', function () {
    $this->get(route('entrance.analytics'))->assertRedirect(route('login'));
});

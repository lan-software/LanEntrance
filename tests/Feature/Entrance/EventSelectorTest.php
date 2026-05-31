<?php

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('lancore.enabled', true);
    config()->set('lancore.base_url', 'http://lancore.test');
    config()->set('lancore.internal_url', 'http://lancore.test');
    config()->set('lancore.token', 'lci_test_token');

    $this->user = User::factory()->lanCoreUser()->create();
});

it('returns the list of events from LanCore', function () {
    Http::fake(['*/api/entrance/events' => Http::response([
        'events' => [
            ['id' => 1, 'name' => 'Spring LAN', 'start_date' => null, 'end_date' => null],
            ['id' => 2, 'name' => 'Summer LAN', 'start_date' => null, 'end_date' => null],
        ],
    ])]);

    $response = $this->actingAs($this->user)->getJson(route('entrance.events'));

    $response->assertOk()
        ->assertJsonCount(2, 'events')
        ->assertJsonPath('events.0.name', 'Spring LAN');
});

it('returns an empty list when LanCore is unreachable', function () {
    Http::fake(['*/api/entrance/events' => fn () => throw new ConnectionException('refused')]);

    $response = $this->actingAs($this->user)->getJson(route('entrance.events'));

    $response->assertOk()
        ->assertJsonPath('events', []);
});

it('requires authentication to list events', function () {
    $this->getJson(route('entrance.events'))->assertUnauthorized();
});

it('stores the selected event in the session', function () {
    $response = $this->actingAs($this->user)
        ->from(route('dashboard'))
        ->post(route('entrance.events.select'), [
            'event_id' => 7,
            'event_name' => 'Autumn LAN',
        ]);

    $response->assertRedirect(route('dashboard'));

    expect(session('entrance_event_id'))->toBe(7)
        ->and(session('entrance_event_name'))->toBe('Autumn LAN');
});

it('validates the event selection payload', function () {
    $response = $this->actingAs($this->user)
        ->from(route('dashboard'))
        ->post(route('entrance.events.select'), [
            'event_id' => 'not-an-integer',
        ]);

    $response->assertRedirect(route('dashboard'))
        ->assertSessionHasErrors(['event_id', 'event_name']);
});

it('clears the selected event from the session', function () {
    $response = $this->actingAs($this->user)
        ->from(route('dashboard'))
        ->withSession([
            'entrance_event_id' => 7,
            'entrance_event_name' => 'Autumn LAN',
        ])
        ->delete(route('entrance.events.clear'));

    $response->assertRedirect(route('dashboard'));

    expect(session()->has('entrance_event_id'))->toBeFalse()
        ->and(session()->has('entrance_event_name'))->toBeFalse();
});

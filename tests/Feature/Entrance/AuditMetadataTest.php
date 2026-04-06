<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

it('sends operator identity and metadata to LanCore', function () {
    Http::fake(['*/api/entrance/validate' => Http::response(lancoreFixture('validate-valid'))]);

    $user = User::factory()->lanCoreUser(42)->create();

    $this->actingAs($user)->postJson('/api/entrance/validate', [
        'token' => 'test-token',
    ]);

    Http::assertSent(function ($request) {
        return $request['operator_id'] === 42
            && isset($request['operator_session'])
            && isset($request['timestamp'])
            && $request['token'] === 'test-token';
    });
});

it('includes session id in requests', function () {
    Http::fake(['*/api/entrance/checkin' => Http::response(lancoreFixture('checkin-success'))]);

    $user = User::factory()->lanCoreUser(99)->create();

    $this->actingAs($user)->postJson('/api/entrance/checkin', [
        'token' => 'test-token',
        'validation_id' => 'val_123',
    ]);

    Http::assertSent(function ($request) {
        return $request['operator_id'] === 99
            && strlen($request['operator_session']) > 0;
    });
});

it('includes operator identity in override requests', function () {
    Http::fake(['*/api/entrance/override' => Http::response(lancoreFixture('override-success'))]);

    $user = User::factory()->lanCoreUser(55)->withRole(\App\Enums\UserRole::Moderator)->create();

    $this->actingAs($user)->postJson('/api/entrance/override', [
        'token' => 'test-token',
        'validation_id' => 'val_123',
        'reason' => 'Override reason for testing audit metadata inclusion.',
    ]);

    Http::assertSent(function ($request) {
        return $request['operator_id'] === 55
            && $request['reason'] === 'Override reason for testing audit metadata inclusion.';
    });
});

<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('allows moderators to submit overrides', function () {
    Http::fake(['*/api/entrance/override' => Http::response(lancoreFixture('override-success'))]);

    $user = User::factory()->lanCoreUser(42)->withRole(UserRole::Moderator)->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/override', [
        'token' => 'group-restricted-token',
        'validation_id' => 'val_ovrde001',
        'reason' => 'Group leader confirmed all members are present and accounted for.',
    ]);

    $response->assertOk()
        ->assertJsonPath('decision', 'valid')
        ->assertJsonPath('override_id', 'ovr_3d2c1b0a');
});

it('allows admins to submit overrides', function () {
    Http::fake(['*/api/entrance/override' => Http::response(lancoreFixture('override-success'))]);

    $user = User::factory()->lanCoreUser()->withRole(UserRole::Admin)->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/override', [
        'token' => 'token',
        'validation_id' => 'val_123',
        'reason' => 'Admin override for special circumstances at the event.',
    ]);

    $response->assertOk();
});

it('denies override for regular users', function () {
    $user = User::factory()->lanCoreUser()->withRole(UserRole::User)->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/override', [
        'token' => 'token',
        'validation_id' => 'val_123',
        'reason' => 'I want to override this restriction for the attendee.',
    ]);

    $response->assertForbidden();
});

it('requires a reason of at least 10 characters', function () {
    $user = User::factory()->lanCoreUser()->withRole(UserRole::Moderator)->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/override', [
        'token' => 'token',
        'validation_id' => 'val_123',
        'reason' => 'short',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('reason');
});

it('requires a reason to be present', function () {
    $user = User::factory()->lanCoreUser()->withRole(UserRole::Moderator)->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/override', [
        'token' => 'token',
        'validation_id' => 'val_123',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('reason');
});

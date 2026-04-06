<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

it('confirms check-in after valid validation', function () {
    Http::fake(['*/api/entrance/checkin' => Http::response(lancoreFixture('checkin-success'))]);

    $user = User::factory()->lanCoreUser(42)->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/checkin', [
        'token' => 'valid-token-abc123',
        'validation_id' => 'val_8f3a2b1c',
    ]);

    $response->assertOk()
        ->assertJsonPath('decision', 'valid')
        ->assertJsonPath('message', 'Check-in confirmed. Welcome!')
        ->assertJsonPath('checkin_id', 'chk_7b2a1d3e')
        ->assertJsonPath('seating.seat', 'A-42')
        ->assertJsonPath('addons.0.name', 'Pizza Package');
});

it('confirms verify-checkin after verification', function () {
    Http::fake(['*/api/entrance/verify-checkin' => Http::response(lancoreFixture('verify-checkin-success'))]);

    $user = User::factory()->lanCoreUser(42)->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/verify-checkin', [
        'token' => 'student-ticket-token',
        'validation_id' => 'val_verify01',
    ]);

    $response->assertOk()
        ->assertJsonPath('decision', 'valid')
        ->assertJsonPath('checkin_id', 'chk_vrfy001');
});

it('rejects checkin without validation_id', function () {
    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/checkin', [
        'token' => 'some-token',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('validation_id');
});

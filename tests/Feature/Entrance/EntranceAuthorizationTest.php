<?php

use App\Models\User;

it('requires authentication for validate endpoint', function () {
    $response = $this->postJson('/api/entrance/validate', [
        'token' => 'any-token',
    ]);

    $response->assertUnauthorized();
});

it('requires authentication for checkin endpoint', function () {
    $response = $this->postJson('/api/entrance/checkin', [
        'token' => 'any-token',
        'validation_id' => 'val_123',
    ]);

    $response->assertUnauthorized();
});

it('requires authentication for lookup endpoint', function () {
    $response = $this->getJson('/api/entrance/lookup?q=test');

    $response->assertUnauthorized();
});

it('allows verified users to access entrance endpoints', function () {
    \Illuminate\Support\Facades\Http::fake(['*/api/entrance/validate' => \Illuminate\Support\Facades\Http::response(lancoreFixture('validate-valid'))]);

    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/validate', [
        'token' => 'any-token',
    ]);

    $response->assertOk();
});

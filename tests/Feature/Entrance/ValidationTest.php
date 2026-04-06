<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

it('returns valid decision for a valid ticket', function () {
    Http::fake(['*/api/entrance/validate' => Http::response(lancoreFixture('validate-valid'))]);

    $user = User::factory()->lanCoreUser(42)->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/validate', [
        'token' => 'valid-token-abc123',
    ]);

    $response->assertOk()
        ->assertJsonPath('decision', 'valid')
        ->assertJsonPath('attendee.name', 'Max Mustermann')
        ->assertJsonPath('seating.seat', 'A-42')
        ->assertJsonStructure(['decision', 'message', 'validation_id', 'attendee', 'seating', 'addons']);
});

it('returns invalid decision for an unknown ticket', function () {
    Http::fake(['*/api/entrance/validate' => Http::response(lancoreFixture('validate-invalid'))]);

    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/validate', [
        'token' => 'unknown-token',
    ]);

    $response->assertOk()
        ->assertJsonPath('decision', 'invalid');
});

it('returns already_checked_in decision', function () {
    Http::fake(['*/api/entrance/validate' => Http::response(lancoreFixture('validate-already-checked-in'))]);

    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/validate', [
        'token' => 'duplicate-token',
    ]);

    $response->assertOk()
        ->assertJsonPath('decision', 'already_checked_in');
});

it('returns verification_required decision with checks', function () {
    Http::fake(['*/api/entrance/validate' => Http::response(lancoreFixture('validate-verification-required'))]);

    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/validate', [
        'token' => 'student-ticket-token',
    ]);

    $response->assertOk()
        ->assertJsonPath('decision', 'verification_required')
        ->assertJsonPath('verification.checks.0.label', 'Student ID');
});

it('returns payment_required decision with payment details', function () {
    Http::fake(['*/api/entrance/validate' => Http::response(lancoreFixture('validate-payment-required'))]);

    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/validate', [
        'token' => 'pay-on-site-token',
    ]);

    $response->assertOk()
        ->assertJsonPath('decision', 'payment_required')
        ->assertJsonPath('payment.amount', '42.00')
        ->assertJsonPath('payment.currency', 'EUR')
        ->assertJsonPath('payment.items.0.name', 'Weekend Ticket');
});

it('returns override_possible decision with group policy', function () {
    Http::fake(['*/api/entrance/validate' => Http::response(lancoreFixture('validate-override-possible'))]);

    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/validate', [
        'token' => 'group-restricted-token',
    ]);

    $response->assertOk()
        ->assertJsonPath('decision', 'override_possible')
        ->assertJsonPath('override_allowed', true)
        ->assertJsonPath('group_policy.members_checked_in', 2);
});

it('returns denied_by_policy decision', function () {
    Http::fake(['*/api/entrance/validate' => Http::response(lancoreFixture('validate-denied-by-policy'))]);

    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/validate', [
        'token' => 'denied-token',
    ]);

    $response->assertOk()
        ->assertJsonPath('decision', 'denied_by_policy');
});

it('rejects missing token', function () {
    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/validate', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('token');
});

it('rejects token exceeding max length', function () {
    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/validate', [
        'token' => str_repeat('a', 513),
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('token');
});

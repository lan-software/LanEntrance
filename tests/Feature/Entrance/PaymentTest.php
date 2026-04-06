<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

it('confirms payment and completes check-in', function () {
    Http::fake(['*/api/entrance/confirm-payment' => Http::response(lancoreFixture('confirm-payment-success'))]);

    $user = User::factory()->lanCoreUser(42)->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/confirm-payment', [
        'token' => 'pay-on-site-token',
        'validation_id' => 'val_pay00001',
        'payment_method' => 'cash',
        'amount' => '42.00',
    ]);

    $response->assertOk()
        ->assertJsonPath('decision', 'valid')
        ->assertJsonPath('receipt_sent', true)
        ->assertJsonPath('payment_id', 'pay_5e4d3c2b')
        ->assertJsonPath('seating.seat', 'C-03');
});

it('accepts card as payment method', function () {
    Http::fake(['*/api/entrance/confirm-payment' => Http::response(lancoreFixture('confirm-payment-success'))]);

    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/confirm-payment', [
        'token' => 'token',
        'validation_id' => 'val_123',
        'payment_method' => 'card',
        'amount' => '42.00',
    ]);

    $response->assertOk();
});

it('rejects invalid payment method', function () {
    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/confirm-payment', [
        'token' => 'token',
        'validation_id' => 'val_123',
        'payment_method' => 'bitcoin',
        'amount' => '42.00',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('payment_method');
});

it('rejects missing amount', function () {
    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/confirm-payment', [
        'token' => 'token',
        'validation_id' => 'val_123',
        'payment_method' => 'cash',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('amount');
});

it('rejects malformed amount', function () {
    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/confirm-payment', [
        'token' => 'token',
        'validation_id' => 'val_123',
        'payment_method' => 'cash',
        'amount' => '42',
    ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('amount');
});

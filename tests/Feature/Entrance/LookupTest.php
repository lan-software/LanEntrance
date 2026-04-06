<?php

use App\Models\User;
use Illuminate\Support\Facades\Http;

it('returns search results from LanCore', function () {
    Http::fake(['*/api/entrance/search*' => Http::response(lancoreFixture('lookup-results'))]);

    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->getJson('/api/entrance/lookup?q=mustermann');

    $response->assertOk()
        ->assertJsonPath('results.0.name', 'Max Mustermann')
        ->assertJsonPath('results.1.name', 'Maria Mustermann')
        ->assertJsonCount(2, 'results');
});

it('rejects query shorter than 2 characters', function () {
    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->getJson('/api/entrance/lookup?q=x');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('q');
});

it('rejects missing query', function () {
    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->getJson('/api/entrance/lookup');

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('q');
});

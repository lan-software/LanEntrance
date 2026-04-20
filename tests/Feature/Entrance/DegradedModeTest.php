<?php

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\LCT1ContractFixture;

beforeEach(function () {
    config()->set('lancore.entrance.signing_keys_cache_store', 'array');
    config()->set('lancore.token_format.version', 'LCT1');
    Cache::store('array')->flush();

    $fixture = LCT1ContractFixture::build();
    $this->lct1Token = $fixture['token'];
    Cache::store('array')->put(
        'lancore:signing-key:'.$fixture['kid'],
        $fixture['publicKey'],
        3600,
    );
});

it('returns degraded response on LanCore connection timeout', function () {
    Http::fake(['*/api/entrance/validate' => fn () => throw new ConnectionException('Connection timed out')]);

    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/validate', [
        'token' => $this->lct1Token,
    ]);

    $response->assertOk()
        ->assertJsonPath('degraded', true)
        ->assertJsonPath('decision', 'error');
});

it('returns degraded response on LanCore 500 error', function () {
    Http::fake(['*/api/entrance/validate' => Http::response(['error' => 'Internal error'], 500)]);

    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/validate', [
        'token' => $this->lct1Token,
    ]);

    $response->assertOk()
        ->assertJsonPath('degraded', true)
        ->assertJsonPath('decision', 'error');
});

it('returns degraded response on LanCore connection refused', function () {
    Http::fake(['*/api/entrance/validate' => fn () => throw new ConnectionException('Connection refused')]);

    $user = User::factory()->lanCoreUser()->create();

    $response = $this->actingAs($user)->postJson('/api/entrance/validate', [
        'token' => $this->lct1Token,
    ]);

    $response->assertOk()
        ->assertJsonPath('degraded', true);
});

it('does not create any local records during degraded operation', function () {
    Http::fake(['*/api/entrance/validate' => fn () => throw new ConnectionException('timeout')]);

    $user = User::factory()->lanCoreUser()->create();

    $this->actingAs($user)->postJson('/api/entrance/validate', [
        'token' => $this->lct1Token,
    ]);

    expect(DB::table('users')->count())->toBe(1);
});

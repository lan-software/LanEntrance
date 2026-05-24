<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
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

it('sends operator identity and metadata to LanCore', function () {
    Http::fake(['*/api/entrance/validate' => Http::response(lancoreFixture('validate-valid'))]);

    $operatorId = (string) Str::ulid();
    $user = User::factory()->lanCoreUser($operatorId)->create();
    $token = $this->lct1Token;

    $this->actingAs($user)->postJson('/api/entrance/validate', [
        'token' => $token,
    ]);

    Http::assertSent(function ($request) use ($token, $operatorId) {
        return $request['operator_id'] === $operatorId
            && isset($request['operator_session'])
            && isset($request['timestamp'])
            && $request['token'] === $token;
    });
});

it('includes session id in requests', function () {
    Http::fake(['*/api/entrance/checkin' => Http::response(lancoreFixture('checkin-success'))]);

    $operatorId = (string) Str::ulid();
    $user = User::factory()->lanCoreUser($operatorId)->create();

    $this->actingAs($user)->postJson('/api/entrance/checkin', [
        'token' => $this->lct1Token,
        'validation_id' => 'val_123',
    ]);

    Http::assertSent(function ($request) use ($operatorId) {
        return $request['operator_id'] === $operatorId
            && strlen($request['operator_session']) > 0;
    });
});

it('includes operator identity in override requests', function () {
    Http::fake(['*/api/entrance/override' => Http::response(lancoreFixture('override-success'))]);

    $operatorId = (string) Str::ulid();
    $user = User::factory()->lanCoreUser($operatorId)->withRole(UserRole::Moderator)->create();

    $this->actingAs($user)->postJson('/api/entrance/override', [
        'token' => $this->lct1Token,
        'validation_id' => 'val_123',
        'reason' => 'Override reason for testing audit metadata inclusion.',
    ]);

    Http::assertSent(function ($request) use ($operatorId) {
        return $request['operator_id'] === $operatorId
            && $request['reason'] === 'Override reason for testing audit metadata inclusion.';
    });
});

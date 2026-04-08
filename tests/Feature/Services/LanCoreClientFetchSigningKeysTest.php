<?php

use App\Services\Exceptions\LanCoreUnavailableException;
use App\Services\LanCoreClient;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('lancore.enabled', true);
    config()->set('lancore.base_url', 'http://lancore.test');
    config()->set('lancore.internal_url', 'http://lancore.test');
    config()->set('lancore.token', 'lci_integration_token');
    config()->set('lancore.signing_keys_endpoint', 'api/entrance/signing-keys');
});

it('fetches and parses the JWKS keys array', function () {
    Http::fake([
        '*/api/entrance/signing-keys' => Http::response([
            'keys' => [
                ['kid' => 'k1', 'kty' => 'OKP', 'crv' => 'Ed25519', 'x' => 'AAAA'],
                ['kid' => 'k2', 'kty' => 'OKP', 'crv' => 'Ed25519', 'x' => 'BBBB'],
            ],
        ]),
    ]);

    $keys = app(LanCoreClient::class)->fetchSigningKeys();

    expect($keys)->toHaveCount(2)
        ->and($keys[0]['kid'])->toBe('k1');

    Http::assertSent(function ($request) {
        return $request->hasHeader('Authorization', 'Bearer lci_integration_token')
            && str_contains($request->url(), '/api/entrance/signing-keys');
    });
});

it('throws LanCoreUnavailableException on server error', function () {
    Http::fake([
        '*/api/entrance/signing-keys' => Http::response('boom', 500),
    ]);

    app(LanCoreClient::class)->fetchSigningKeys();
})->throws(LanCoreUnavailableException::class);

it('throws RuntimeException on 401 unauthorized', function () {
    Http::fake([
        '*/api/entrance/signing-keys' => Http::response(['error' => 'unauthorized'], 401),
    ]);

    app(LanCoreClient::class)->fetchSigningKeys();
})->throws(RuntimeException::class);

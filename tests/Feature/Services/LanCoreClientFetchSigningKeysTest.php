<?php

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use LanSoftware\LanCoreClient\Exceptions\LanCoreRequestException;
use LanSoftware\LanCoreClient\Exceptions\LanCoreUnavailableException;
use LanSoftware\LanCoreClient\LanCoreClient;

beforeEach(function () {
    config()->set('lancore.enabled', true);
    config()->set('lancore.base_url', 'http://lancore.test');
    config()->set('lancore.internal_url', 'http://lancore.test');
    config()->set('lancore.token', 'lci_integration_token');
    config()->set('lancore.entrance.enabled', true);
    config()->set('lancore.entrance.signing_keys_endpoint', 'api/entrance/signing-keys');
    config()->set('lancore.entrance.signing_keys_cache_store', 'array');

    // Ensure no JWKS payload is cached from a prior test run — the client caches
    // successful lookups and would short-circuit the HTTP fake otherwise.
    Cache::store('array')->forget('lancore.jwks');
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

    $keys = app(LanCoreClient::class)->entrance()->fetchSigningKeys();

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

    app(LanCoreClient::class)->entrance()->fetchSigningKeys();
})->throws(LanCoreUnavailableException::class);

it('throws LanCoreRequestException on 401 unauthorized', function () {
    Http::fake([
        '*/api/entrance/signing-keys' => Http::response(['error' => 'unauthorized'], 401),
    ]);

    app(LanCoreClient::class)->entrance()->fetchSigningKeys();
})->throws(LanCoreRequestException::class);

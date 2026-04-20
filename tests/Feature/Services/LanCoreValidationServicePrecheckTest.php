<?php

use App\Models\User;
use App\Services\LanCoreValidationService;
use App\Services\TicketSignatureVerifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('lancore.enabled', true);
    config()->set('lancore.base_url', 'http://lancore.test');
    config()->set('lancore.internal_url', 'http://lancore.test');
    config()->set('lancore.token', 'lci_integration_token');
    config()->set('lancore.entrance.signing_keys_cache_store', 'array');
    config()->set('lancore.token_format.version', 'LCT1');

    Cache::store('array')->flush();

    $kp = sodium_crypto_sign_keypair();
    $this->publicKey = sodium_crypto_sign_publickey($kp);
    $this->secretKey = sodium_crypto_sign_secretkey($kp);
    $this->kid = 'svc-key';
});

function makeValidToken($secretKey, string $kid): string
{
    $body = TicketSignatureVerifier::base64UrlEncode(json_encode([
        'tid' => 1, 'nonce' => 'n', 'iat' => time() - 1, 'exp' => time() + 3600, 'evt' => 1,
    ]));
    $signingInput = 'LCT1.'.$kid.'.'.$body;
    $sig = sodium_crypto_sign_detached($signingInput, $secretKey);

    return $signingInput.'.'.TicketSignatureVerifier::base64UrlEncode($sig);
}

it('valid pre-check delegates to LanCore validate endpoint', function () {
    Http::fake([
        '*/api/entrance/signing-keys' => Http::response([
            'keys' => [[
                'kid' => $this->kid, 'kty' => 'OKP', 'crv' => 'Ed25519',
                'x' => TicketSignatureVerifier::base64UrlEncode($this->publicKey),
            ]],
        ]),
        '*/api/entrance/validate' => Http::response(lancoreFixture('validate-valid')),
    ]);

    $token = makeValidToken($this->secretKey, $this->kid);
    $user = User::factory()->lanCoreUser()->create();

    $result = app(LanCoreValidationService::class)->validate($token, $user);

    expect($result['decision'])->toBe('valid');
    Http::assertSent(fn ($req) => str_contains($req->url(), '/api/entrance/validate'));
});

it('invalid signature short-circuits without calling validate', function () {
    Http::fake([
        '*/api/entrance/signing-keys' => Http::response([
            'keys' => [[
                'kid' => $this->kid, 'kty' => 'OKP', 'crv' => 'Ed25519',
                'x' => TicketSignatureVerifier::base64UrlEncode($this->publicKey),
            ]],
        ]),
        '*/api/entrance/validate' => Http::response(lancoreFixture('validate-valid')),
    ]);

    // Sign with a different key so signature won't verify against the published one
    $other = sodium_crypto_sign_keypair();
    $token = makeValidToken(sodium_crypto_sign_secretkey($other), $this->kid);

    $user = User::factory()->lanCoreUser()->create();
    $result = app(LanCoreValidationService::class)->validate($token, $user);

    expect($result['decision'])->toBe('invalid_signature');
    Http::assertNotSent(fn ($req) => str_contains($req->url(), '/api/entrance/validate'));
});

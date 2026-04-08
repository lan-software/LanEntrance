<?php

use App\Services\Exceptions\ExpiredTokenException;
use App\Services\Exceptions\InvalidSignatureException;
use App\Services\Exceptions\MalformedTokenException;
use App\Services\Exceptions\UnknownKidException;
use App\Services\TicketSignatureVerifier;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\Fixtures\LCT1ContractFixture;

function makeToken(array $overrides = [], ?string $secretKey = null, string $kid = 'test-key'): string
{
    if ($secretKey === null) {
        $keypair = sodium_crypto_sign_keypair();
        $secretKey = sodium_crypto_sign_secretkey($keypair);
    }

    $claims = array_merge([
        'tid' => 1,
        'nonce' => 'abc',
        'iat' => time() - 60,
        'exp' => time() + 3600,
        'evt' => 1,
    ], $overrides);

    $body = TicketSignatureVerifier::base64UrlEncode(json_encode($claims, JSON_THROW_ON_ERROR));
    $signingInput = 'LCT1.'.$kid.'.'.$body;
    $sig = sodium_crypto_sign_detached($signingInput, $secretKey);

    return $signingInput.'.'.TicketSignatureVerifier::base64UrlEncode($sig);
}

function jwksFor(string $publicKey, string $kid): array
{
    return [
        'keys' => [
            [
                'kid' => $kid,
                'kty' => 'OKP',
                'crv' => 'Ed25519',
                'x' => TicketSignatureVerifier::base64UrlEncode($publicKey),
            ],
        ],
    ];
}

beforeEach(function () {
    config()->set('lancore.enabled', true);
    config()->set('lancore.signing_keys_cache_store', 'array');
    config()->set('lancore.signing_keys_cache_ttl', 3600);
    config()->set('lancore.signing_keys_bootstrap', []);
    config()->set('lancore.token_format.version', 'LCT1');

    Cache::store('array')->flush();

    $kp = sodium_crypto_sign_keypair();
    $this->publicKey = sodium_crypto_sign_publickey($kp);
    $this->secretKey = sodium_crypto_sign_secretkey($kp);
    $this->kid = 'test-key';
});

it('verifies a valid LCT1 token', function () {
    Http::fake([
        '*/api/entrance/signing-keys' => Http::response(jwksFor($this->publicKey, $this->kid)),
    ]);

    $token = makeToken([], $this->secretKey, $this->kid);

    $verifier = app(TicketSignatureVerifier::class);
    $result = $verifier->verify($token);

    expect($result->kid)->toBe($this->kid)
        ->and($result->tid)->toBe(1)
        ->and($result->evt)->toBe(1);
});

it('rejects a tampered body', function () {
    Http::fake([
        '*/api/entrance/signing-keys' => Http::response(jwksFor($this->publicKey, $this->kid)),
    ]);

    $token = makeToken([], $this->secretKey, $this->kid);
    [$prefix, $kid, $body, $sig] = explode('.', $token);
    $tamperedBody = TicketSignatureVerifier::base64UrlEncode(json_encode([
        'tid' => 999, 'nonce' => 'x', 'iat' => time(), 'exp' => time() + 3600, 'evt' => 1,
    ]));
    $tampered = "$prefix.$kid.$tamperedBody.$sig";

    app(TicketSignatureVerifier::class)->verify($tampered);
})->throws(InvalidSignatureException::class);

it('rejects a tampered signature', function () {
    Http::fake([
        '*/api/entrance/signing-keys' => Http::response(jwksFor($this->publicKey, $this->kid)),
    ]);

    $token = makeToken([], $this->secretKey, $this->kid);
    [$prefix, $kid, $body, $sig] = explode('.', $token);
    $sigBin = TicketSignatureVerifier::base64UrlDecode($sig);
    $sigBin[0] = chr(ord($sigBin[0]) ^ 0xFF);
    $tampered = "$prefix.$kid.$body.".TicketSignatureVerifier::base64UrlEncode($sigBin);

    app(TicketSignatureVerifier::class)->verify($tampered);
})->throws(InvalidSignatureException::class);

it('rejects unknown kid', function () {
    Http::fake([
        '*/api/entrance/signing-keys' => Http::response(['keys' => []]),
    ]);

    $token = makeToken([], $this->secretKey, 'nope');

    app(TicketSignatureVerifier::class)->verify($token);
})->throws(UnknownKidException::class);

it('rejects expired token', function () {
    Http::fake([
        '*/api/entrance/signing-keys' => Http::response(jwksFor($this->publicKey, $this->kid)),
    ]);

    $token = makeToken(['exp' => time() - 10], $this->secretKey, $this->kid);

    app(TicketSignatureVerifier::class)->verify($token);
})->throws(ExpiredTokenException::class);

it('rejects malformed segments', function () {
    app(TicketSignatureVerifier::class)->verify('not.a.token');
})->throws(MalformedTokenException::class);

it('rejects wrong prefix', function () {
    app(TicketSignatureVerifier::class)->verify('XXX1.k.b.s');
})->throws(MalformedTokenException::class);

it('caches signing keys after first fetch', function () {
    Http::fake([
        '*/api/entrance/signing-keys' => Http::response(jwksFor($this->publicKey, $this->kid)),
    ]);

    $verifier = app(TicketSignatureVerifier::class);
    $token = makeToken([], $this->secretKey, $this->kid);

    $verifier->verify($token);
    $verifier->verify($token);

    Http::assertSentCount(1);
});

it('falls back to bootstrap key when LanCore is unreachable', function () {
    Http::fake([
        '*/api/entrance/signing-keys' => fn () => throw new ConnectionException('down'),
    ]);

    config()->set('lancore.signing_keys_bootstrap', [
        ['kid' => $this->kid, 'x' => TicketSignatureVerifier::base64UrlEncode($this->publicKey)],
    ]);

    $token = makeToken([], $this->secretKey, $this->kid);
    $result = app(TicketSignatureVerifier::class)->verify($token);

    expect($result->kid)->toBe($this->kid);
});

it('verifies the LCT1 contract fixture', function () {
    $fixture = LCT1ContractFixture::build();

    Http::fake([
        '*/api/entrance/signing-keys' => Http::response([
            'keys' => [[
                'kid' => $fixture['kid'],
                'kty' => 'OKP',
                'crv' => 'Ed25519',
                'x' => $fixture['publicKeyB64Url'],
            ]],
        ]),
    ]);

    $result = app(TicketSignatureVerifier::class)->verify($fixture['token']);

    expect($result->kid)->toBe(LCT1ContractFixture::KID)
        ->and($result->tid)->toBe(LCT1ContractFixture::TID)
        ->and($result->evt)->toBe(LCT1ContractFixture::EVT);
});

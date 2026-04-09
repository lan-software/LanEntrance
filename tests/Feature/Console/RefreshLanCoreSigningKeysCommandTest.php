<?php

use App\Services\TicketSignatureVerifier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    config()->set('lancore.enabled', true);
    config()->set('lancore.entrance.enabled', true);
    config()->set('lancore.entrance.signing_keys_cache_store', 'array');
    config()->set('lancore.entrance.signing_keys_cache_ttl', 1800);
    Cache::store('array')->flush();
});

it('refreshes signing keys into the cache', function () {
    $kp = sodium_crypto_sign_keypair();
    $pub = sodium_crypto_sign_publickey($kp);

    Http::fake([
        '*/api/entrance/signing-keys' => Http::response([
            'keys' => [[
                'kid' => 'cmd-key', 'kty' => 'OKP', 'crv' => 'Ed25519',
                'x' => TicketSignatureVerifier::base64UrlEncode($pub),
            ]],
        ]),
    ]);

    $this->artisan('lancore:keys:refresh', ['--no-interaction' => true])
        ->expectsOutputToContain('Cached 1 LanCore signing key(s).')
        ->assertSuccessful();

    expect(Cache::store('array')->get('lancore:signing-key:cmd-key'))->toBe($pub);
});

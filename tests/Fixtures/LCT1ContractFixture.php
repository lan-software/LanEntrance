<?php

namespace Tests\Fixtures;

use App\Services\TicketSignatureVerifier;

/**
 * LCT1 Contract Fixture — canonical test vector shared between LanCore and LanEntrance.
 *
 * Both repositories MUST keep this fixture in sync. The seed below is the single
 * source of truth; the public key, kid, body claims, and resulting token are all
 * derived deterministically from it. A follow-up PR should mirror this file in
 * LanCore (LanCore/tests/Fixtures/LCT1ContractFixture.php) using identical values.
 *
 * Seed (32 bytes, hex): 4c43543143544f4e545241435431323334353637383930313233343536373839
 * Kid: contract-1
 * Body claims:
 *   tid:   1
 *   nonce: AAECAwQFBgcICQoLDA0ODw  (base64url of 0x00..0x0f, 16 bytes)
 *   iat:   1700000000
 *   exp:   4102444800   (year 2100 — far future, never expires in tests)
 *   evt:   42
 *
 * The token, signing input, and signature are computed once at runtime from
 * the seed via libsodium so the fixture is self-verifying.
 */
final class LCT1ContractFixture
{
    public const SEED_HEX = '4c43543143544f4e545241435431323334353637383930313233343536373839';

    public const KID = 'contract-1';

    public const TID = 1;

    public const NONCE = 'AAECAwQFBgcICQoLDA0ODw';

    public const IAT = 1700000000;

    public const EXP = 4102444800;

    public const EVT = 42;

    /**
     * @return array{seed:string, publicKey:string, secretKey:string, kid:string, body:string, token:string, publicKeyB64Url:string}
     */
    public static function build(): array
    {
        $seed = hex2bin(self::SEED_HEX);
        $keypair = sodium_crypto_sign_seed_keypair($seed);
        $publicKey = sodium_crypto_sign_publickey($keypair);
        $secretKey = sodium_crypto_sign_secretkey($keypair);

        $bodyJson = json_encode([
            'tid' => self::TID,
            'nonce' => self::NONCE,
            'iat' => self::IAT,
            'exp' => self::EXP,
            'evt' => self::EVT,
        ], JSON_THROW_ON_ERROR);

        $body = TicketSignatureVerifier::base64UrlEncode($bodyJson);
        $signingInput = 'LCT1.'.self::KID.'.'.$body;
        $sig = sodium_crypto_sign_detached($signingInput, $secretKey);
        $token = $signingInput.'.'.TicketSignatureVerifier::base64UrlEncode($sig);

        return [
            'seed' => $seed,
            'publicKey' => $publicKey,
            'secretKey' => $secretKey,
            'kid' => self::KID,
            'body' => $body,
            'token' => $token,
            'publicKeyB64Url' => TicketSignatureVerifier::base64UrlEncode($publicKey),
        ];
    }
}

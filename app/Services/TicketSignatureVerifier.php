<?php

namespace App\Services;

use App\Services\Exceptions\ExpiredTokenException;
use App\Services\Exceptions\InvalidSignatureException;
use App\Services\Exceptions\MalformedTokenException;
use App\Services\Exceptions\UnknownKidException;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LanSoftware\LanCoreClient\Exceptions\LanCoreUnavailableException;
use LanSoftware\LanCoreClient\LanCoreClient;
use Throwable;

class TicketSignatureVerifier
{
    public function __construct(
        private readonly LanCoreClient $client,
        private readonly CacheFactory $cache,
        private readonly ConfigRepository $config,
    ) {}

    public function verify(string $payload): VerifiedToken
    {
        $segments = explode('.', $payload);

        if (count($segments) !== 4) {
            throw new MalformedTokenException('Token must have four segments.');
        }

        [$prefix, $kid, $body, $sig] = $segments;

        if ($prefix !== ($this->config->get('entrance.token_format.version') ?? 'LCT1')) {
            throw new MalformedTokenException('Unsupported token version.');
        }

        if ($kid === '' || $body === '' || $sig === '') {
            throw new MalformedTokenException('Token segments must not be empty.');
        }

        $publicKey = $this->publicKeyFor($kid);

        $signingInput = $prefix.'.'.$kid.'.'.$body;
        $sigBin = self::base64UrlDecode($sig);

        if ($sigBin === false) {
            throw new MalformedTokenException('Signature is not valid base64url.');
        }

        try {
            $valid = sodium_crypto_sign_verify_detached($sigBin, $signingInput, $publicKey);
        } catch (Throwable $e) {
            throw new InvalidSignatureException('Signature verification failed.', 0, $e);
        }

        if (! $valid) {
            throw new InvalidSignatureException('Signature does not match.');
        }

        $bodyJson = self::base64UrlDecode($body);

        if ($bodyJson === false) {
            throw new MalformedTokenException('Body is not valid base64url.');
        }

        $decoded = json_decode($bodyJson, true);

        if (! is_array($decoded) || ! isset($decoded['tid'], $decoded['nonce'], $decoded['iat'], $decoded['exp'], $decoded['evt'])) {
            throw new MalformedTokenException('Token body missing required claims.');
        }

        $exp = (int) $decoded['exp'];

        if ($exp < time()) {
            throw new ExpiredTokenException('Token has expired.');
        }

        return new VerifiedToken(
            tid: (int) $decoded['tid'],
            nonce: (string) $decoded['nonce'],
            iat: (int) $decoded['iat'],
            exp: $exp,
            evt: (int) $decoded['evt'],
            kid: $kid,
        );
    }

    public function publicKeyFor(string $kid): string
    {
        $store = $this->cacheStore();
        $cacheKey = $this->cacheKey($kid);

        $cached = $store->get($cacheKey);

        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        try {
            $keys = $this->client->entrance()->fetchSigningKeys();
            $this->storeKeys($keys);

            $fresh = $store->get($cacheKey);

            if (is_string($fresh) && $fresh !== '') {
                return $fresh;
            }
        } catch (LanCoreUnavailableException $e) {
            $bootstrap = $this->bootstrapKey($kid);

            if ($bootstrap !== null) {
                return $bootstrap;
            }

            throw $e;
        }

        $bootstrap = $this->bootstrapKey($kid);

        if ($bootstrap !== null) {
            return $bootstrap;
        }

        throw new UnknownKidException("Unknown signing key id: {$kid}");
    }

    /**
     * @param  array<int, array<string, mixed>>  $keys
     */
    private function storeKeys(array $keys): void
    {
        $store = $this->cacheStore();
        $ttl = (int) $this->config->get('lancore.entrance.signing_keys_cache_ttl', 3600);

        foreach ($keys as $key) {
            if (! is_array($key) || ! isset($key['kid'], $key['x'])) {
                continue;
            }

            $bin = self::base64UrlDecode((string) $key['x']);

            if ($bin === false || strlen($bin) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
                continue;
            }

            $store->put($this->cacheKey((string) $key['kid']), $bin, $ttl);
        }
    }

    private function bootstrapKey(string $kid): ?string
    {
        $bootstrap = $this->config->get('lancore.entrance.signing_keys_bootstrap', '');

        if (! is_string($bootstrap) || $bootstrap === '') {
            return null;
        }

        foreach (explode(',', $bootstrap) as $pair) {
            $parts = explode(':', trim($pair), 2);

            if (count($parts) !== 2) {
                continue;
            }

            [$entryKid, $x] = $parts;

            if ($entryKid !== $kid) {
                continue;
            }

            $bin = self::base64UrlDecode($x);

            if ($bin === false || strlen($bin) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
                continue;
            }

            return $bin;
        }

        return null;
    }

    private function cacheStore()
    {
        $store = (string) $this->config->get('lancore.entrance.signing_keys_cache_store', 'file');

        return $this->cache->store($store);
    }

    private function cacheKey(string $kid): string
    {
        return 'lancore:signing-key:'.$kid;
    }

    public static function base64UrlEncode(string $bin): string
    {
        return strtr(rtrim(base64_encode($bin), '='), '+/', '-_');
    }

    /**
     * @return string|false
     */
    public static function base64UrlDecode(string $input)
    {
        $padded = strtr($input, '-_', '+/');
        $remainder = strlen($padded) % 4;

        if ($remainder !== 0) {
            $padded .= str_repeat('=', 4 - $remainder);
        }

        return base64_decode($padded, true);
    }
}

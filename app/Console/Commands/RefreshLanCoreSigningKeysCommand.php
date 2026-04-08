<?php

namespace App\Console\Commands;

use App\Services\LanCoreClient;
use App\Services\TicketSignatureVerifier;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Factory as CacheFactory;

class RefreshLanCoreSigningKeysCommand extends Command
{
    protected $signature = 'lancore:keys:refresh';

    protected $description = 'Fetch the LanCore JWKS and warm the local signing-key cache.';

    public function handle(LanCoreClient $client, CacheFactory $cache): int
    {
        $keys = $client->fetchSigningKeys();

        $store = $cache->store((string) config('lancore.signing_keys_cache_store', 'file'));
        $ttl = (int) config('lancore.signing_keys_cache_ttl', 3600);

        $stored = 0;

        foreach ($keys as $key) {
            if (! is_array($key) || ! isset($key['kid'], $key['x'])) {
                continue;
            }

            $bin = TicketSignatureVerifier::base64UrlDecode((string) $key['x']);

            if ($bin === false || strlen($bin) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
                $this->warn('Skipped invalid key: '.$key['kid']);

                continue;
            }

            $store->put('lancore:signing-key:'.$key['kid'], $bin, $ttl);
            $stored++;
        }

        $this->info("Cached {$stored} LanCore signing key(s).");

        return self::SUCCESS;
    }
}

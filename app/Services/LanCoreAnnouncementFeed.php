<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class LanCoreAnnouncementFeed
{
    private const CACHE_KEY = 'lancore.announcements.feed';

    private const CACHE_TTL = 60;

    /**
     * Fetch announcements from LanCore's public feed.
     *
     * @return array<int, array{id:int|string, audience:?string, severity:?string, title:string, body:?string, starts_at:?string, ends_at:?string, dismissible:bool}>
     */
    public function fetch(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function (): array {
            $url = (string) config('entrance.announcements_feed_url');

            if ($url === '') {
                return [];
            }

            try {
                $response = Http::timeout(2)
                    ->retry(1, 100, throw: false)
                    ->acceptJson()
                    ->get($url);
            } catch (Throwable) {
                return [];
            }

            if (! $response->successful()) {
                return [];
            }

            try {
                $payload = $response->json();
            } catch (Throwable) {
                return [];
            }

            $items = is_array($payload) && isset($payload['data']) && is_array($payload['data'])
                ? $payload['data']
                : (is_array($payload) ? $payload : []);

            if (! is_array($items)) {
                return [];
            }

            return array_values(array_filter(
                array_map(static fn ($item): ?array => is_array($item) ? [
                    'id' => $item['id'] ?? null,
                    'audience' => $item['audience'] ?? null,
                    'severity' => $item['severity'] ?? null,
                    'title' => (string) ($item['title'] ?? ''),
                    'body' => $item['body'] ?? null,
                    'starts_at' => $item['starts_at'] ?? null,
                    'ends_at' => $item['ends_at'] ?? null,
                    'dismissible' => (bool) ($item['dismissible'] ?? true),
                ] : null, $items),
                static fn (?array $item): bool => $item !== null && $item['id'] !== null,
            ));
        });
    }
}

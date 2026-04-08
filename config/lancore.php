<?php

$bootstrapKeys = array_values(array_filter(array_map(function (string $entry): ?array {
    $entry = trim($entry);

    if ($entry === '' || ! str_contains($entry, ':')) {
        return null;
    }

    [$kid, $x] = explode(':', $entry, 2);
    $kid = trim($kid);
    $x = trim($x);

    if ($kid === '' || $x === '') {
        return null;
    }

    return ['kid' => $kid, 'x' => $x];
}, explode(',', (string) env('LANCORE_SIGNING_KEYS_BOOTSTRAP', '')))));

return [
    'enabled' => env('LANCORE_ENABLED', false),
    'base_url' => env('LANCORE_BASE_URL', 'http://lancore.lan'),
    'internal_url' => env('LANCORE_INTERNAL_URL') ?? env('LANCORE_BASE_URL', 'http://lancore.lan'),
    'token' => env('LANCORE_TOKEN'),
    // Note: 'token' above is the integration bearer token (existing). LCT1
    // ticket-token format settings live under 'token_format' below to avoid
    // a breaking rename.
    'app_slug' => env('LANCORE_APP_SLUG', 'lanentrance'),
    'callback_url' => env('LANCORE_CALLBACK_URL', env('APP_URL').'/auth/callback'),
    'roles_webhook_secret' => env('LANCORE_ROLES_WEBHOOK_SECRET'),
    'announcements_feed_url' => env('LANCORE_ANNOUNCEMENTS_FEED_URL', rtrim((string) env('LANCORE_BASE_URL', 'http://lancore.lan'), '/').'/api/announcements/feed'),

    'timeout' => (int) env('LANCORE_TIMEOUT', 5),
    'retries' => (int) env('LANCORE_RETRIES', 2),
    'retry_delay' => (int) env('LANCORE_RETRY_DELAY', 100),

    /*
    |--------------------------------------------------------------------------
    | LCT1 Signed Ticket Tokens
    |--------------------------------------------------------------------------
    |
    | LanCore issues Ed25519-signed LCT1 tokens. LanEntrance fetches the public
    | keys via JWKS, caches them, and performs a fast local signature pre-check
    | before invoking the authoritative validate endpoint. There is no offline
    | mode — LanCore remains the source of truth.
    |
    */

    'signing_keys_endpoint' => env('LANCORE_SIGNING_KEYS_ENDPOINT', 'api/entrance/signing-keys'),
    'signing_keys_cache_ttl' => (int) env('LANCORE_SIGNING_KEYS_CACHE_TTL', 3600),
    'signing_keys_cache_store' => env('LANCORE_SIGNING_KEYS_CACHE_STORE', 'file'),
    'signing_keys_bootstrap' => $bootstrapKeys,

    'token_format' => [
        'version' => 'LCT1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Branding
    |--------------------------------------------------------------------------
    |
    | Customize the entrance UI appearance per event. These values are shared
    | with the frontend via Inertia shared props. Set via environment variables
    | or leave empty for defaults.
    |
    */

    'event_name' => env('LANCORE_EVENT_NAME', ''),
    'event_logo' => env('LANCORE_EVENT_LOGO', ''),
    'primary_color' => env('LANCORE_PRIMARY_COLOR', ''),
];

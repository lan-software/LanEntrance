<?php

return [
    'enabled' => env('LANCORE_ENABLED', false),
    'base_url' => env('LANCORE_BASE_URL', 'http://lancore.lan'),
    'internal_url' => env('LANCORE_INTERNAL_URL') ?? env('LANCORE_BASE_URL', 'http://lancore.lan'),
    'token' => env('LANCORE_TOKEN'),
    'app_slug' => env('LANCORE_APP_SLUG', 'lanentrance'),
    'callback_url' => env('LANCORE_CALLBACK_URL', env('APP_URL').'/auth/callback'),
    'roles_webhook_secret' => env('LANCORE_ROLES_WEBHOOK_SECRET'),
    'timeout' => (int) env('LANCORE_TIMEOUT', 5),
    'retries' => (int) env('LANCORE_RETRIES', 2),
    'retry_delay' => (int) env('LANCORE_RETRY_DELAY', 100),

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

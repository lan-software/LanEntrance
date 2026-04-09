<?php

return [
    /*
    |--------------------------------------------------------------------------
    | LCT1 Signed Ticket Tokens
    |--------------------------------------------------------------------------
    */
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

    /*
    |--------------------------------------------------------------------------
    | Announcements Feed
    |--------------------------------------------------------------------------
    */
    'announcements_feed_url' => env('LANCORE_ANNOUNCEMENTS_FEED_URL', rtrim((string) env('LANCORE_BASE_URL', 'http://lancore.lan'), '/').'/api/announcements/feed'),
];

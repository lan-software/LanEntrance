<?php

return [
    'app_slug' => env('LANCORE_APP_SLUG', 'lanentrance'),

    /*
    |--------------------------------------------------------------------------
    | Announcements Feed
    |--------------------------------------------------------------------------
    |
    | URL of the public LanCore announcements feed consumed by LanEntrance.
    | Falls back to LANCORE_INTERNAL_URL (then LANCORE_BASE_URL) + the feed path.
    |
    */
    'announcements_feed_url' => env(
        'LANCORE_ANNOUNCEMENTS_FEED_URL',
        rtrim((string) (env('LANCORE_INTERNAL_URL') ?: env('LANCORE_BASE_URL', 'http://lancore.lan')), '/').'/api/announcements/feed'
    ),
];

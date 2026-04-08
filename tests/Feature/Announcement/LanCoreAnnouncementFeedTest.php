<?php

use App\Services\LanCoreAnnouncementFeed;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function (): void {
    Cache::flush();
    config()->set('lancore.announcements_feed_url', 'https://lancore.test/api/announcements/feed');
});

it('returns array on 200', function (): void {
    Http::fake([
        'lancore.test/*' => Http::response(['data' => [[
            'id' => 1,
            'audience' => 'satellites',
            'severity' => 'info',
            'title' => 'Hello',
            'body' => 'World',
            'starts_at' => null,
            'ends_at' => null,
            'dismissible' => true,
        ]]], 200),
    ]);

    $result = app(LanCoreAnnouncementFeed::class)->fetch();

    expect($result)->toHaveCount(1)
        ->and($result[0]['id'])->toBe(1)
        ->and($result[0]['title'])->toBe('Hello');
});

it('returns empty array on 500', function (): void {
    Http::fake([
        'lancore.test/*' => Http::response('err', 500),
    ]);

    expect(app(LanCoreAnnouncementFeed::class)->fetch())->toBe([]);
});

it('returns empty array on connection failure', function (): void {
    Http::fake(function (): void {
        throw new Exception('connection refused');
    });

    expect(app(LanCoreAnnouncementFeed::class)->fetch())->toBe([]);
});

it('caches the result', function (): void {
    Http::fake([
        'lancore.test/*' => Http::response(['data' => []], 200),
    ]);

    $service = app(LanCoreAnnouncementFeed::class);
    $service->fetch();
    $service->fetch();

    Http::assertSentCount(1);
});

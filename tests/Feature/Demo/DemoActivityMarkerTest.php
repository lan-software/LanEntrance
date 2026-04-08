<?php

use App\Models\User;
use Illuminate\Support\Facades\Redis;

it('records activity when demo mode is enabled and user is authenticated', function (): void {
    config()->set('app.demo', true);

    Redis::shouldReceive('set')
        ->once()
        ->withArgs(fn (string $key, string $value): bool => $key === 'demo:last_activity');

    $user = User::factory()->create();

    $this->actingAs($user)->get('/');
});

it('does nothing when demo mode is disabled', function (): void {
    config()->set('app.demo', false);

    Redis::shouldReceive('set')->never();

    $user = User::factory()->create();

    $this->actingAs($user)->get('/');
});

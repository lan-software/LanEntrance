<?php

it('blocks registration when demo mode is enabled', function (): void {
    config()->set('app.demo', true);

    $response = $this->post('/register', [
        'name' => 'Demo',
        'email' => 'demo@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($response->status())->toBe(403);
});

it('does not block registration when demo mode is disabled', function (): void {
    config()->set('app.demo', false);

    $response = $this->post('/register', [
        'name' => 'User',
        'email' => 'user@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($response->status())->toBeIn([200, 201, 204, 302, 404, 405, 422]);
});

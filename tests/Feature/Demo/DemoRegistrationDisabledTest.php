<?php

use App\Providers\FortifyServiceProvider;

it('removes fortify registration route when demo mode is enabled', function (): void {
    config()->set('app.demo', true);

    $this->app->register(FortifyServiceProvider::class, true);

    $response = $this->post('/register', [
        'name' => 'Demo',
        'email' => 'demo@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($response->status())->toBeIn([403, 404, 405]);
});

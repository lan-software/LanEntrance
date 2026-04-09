<?php

use App\Http\Controllers\Api\LanCoreRolesWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/roles', LanCoreRolesWebhookController::class)
    ->middleware('lancore.webhook:user.roles_updated')
    ->name('api.webhooks.roles');

Route::post('webhook/roles', LanCoreRolesWebhookController::class)
    ->middleware('lancore.webhook:user.roles_updated');

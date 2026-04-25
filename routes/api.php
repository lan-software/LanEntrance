<?php

use App\Http\Controllers\Api\LanCoreRolesWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/roles', LanCoreRolesWebhookController::class)
    ->middleware('lancore.webhook:user.roles_updated')
    ->name('api.webhooks.roles');

// Deprecated 2026-04: legacy singular form. Remove after one minor release once LanCore
// has migrated all deployments off the /api/webhooks/lancore/<event> default.
Route::post('webhook/roles', LanCoreRolesWebhookController::class)
    ->middleware('lancore.webhook:user.roles_updated');

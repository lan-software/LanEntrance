<?php

use App\Http\Controllers\Api\LanCoreRolesWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/roles', LanCoreRolesWebhookController::class)->name('api.webhooks.roles');
Route::post('webhook/roles', LanCoreRolesWebhookController::class);
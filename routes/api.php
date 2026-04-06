<?php

use App\Http\Controllers\Api\LanCoreRolesWebhookController;
use App\Http\Controllers\Entrance\EntranceController;
use App\Http\Controllers\Entrance\LookupController;
use App\Http\Controllers\Entrance\OverrideController;
use App\Http\Controllers\Entrance\PaymentController;
use Illuminate\Support\Facades\Route;

Route::post('webhooks/roles', LanCoreRolesWebhookController::class)->name('api.webhooks.roles');
Route::post('webhook/roles', LanCoreRolesWebhookController::class);

// ── Entrance API ────────────────────────────────────────────────────

Route::middleware(['auth', 'verified'])->prefix('entrance')->group(function () {
    Route::post('/validate', [EntranceController::class, 'validate'])
        ->middleware('throttle:entrance')
        ->name('api.entrance.validate');

    Route::post('/checkin', [EntranceController::class, 'checkin'])
        ->middleware('throttle:entrance')
        ->name('api.entrance.checkin');

    Route::post('/verify-checkin', [EntranceController::class, 'verifyCheckin'])
        ->middleware('throttle:entrance')
        ->name('api.entrance.verify-checkin');

    Route::post('/confirm-payment', PaymentController::class)
        ->middleware('throttle:entrance')
        ->name('api.entrance.confirm-payment');

    Route::post('/override', OverrideController::class)
        ->middleware(['throttle:entrance', 'entrance.role:moderator'])
        ->name('api.entrance.override');

    Route::get('/lookup', LookupController::class)
        ->middleware('throttle:entrance')
        ->name('api.entrance.lookup');
});
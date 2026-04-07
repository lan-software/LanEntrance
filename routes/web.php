<?php

use App\Http\Controllers\Auth\LanCoreAuthController;
use App\Http\Controllers\Entrance\AnalyticsController;
use App\Http\Controllers\Entrance\EntranceController;
use App\Http\Controllers\Entrance\EventSelectorController;
use App\Http\Controllers\Entrance\LookupController;
use App\Http\Controllers\Entrance\OverrideController;
use App\Http\Controllers\Entrance\PaymentController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    if (config('lancore.enabled') && ! session()->has('error')) {
        return redirect()->route('auth.redirect');
    }

    return inertia('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::prefix('auth')->name('auth.')->group(function () {
    Route::get('redirect', [LanCoreAuthController::class, 'redirect'])->name('redirect');
    Route::get('callback', [LanCoreAuthController::class, 'callback'])->name('callback');
    Route::get('status', [LanCoreAuthController::class, 'status'])->name('status');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::inertia('entrance', 'entrance/Scanner')->name('entrance.scanner');
    Route::inertia('entrance/lookup', 'entrance/Lookup')->name('entrance.lookup');
    Route::get('entrance/analytics', AnalyticsController::class)
        ->middleware('entrance.role:admin')
        ->name('entrance.analytics');

    Route::get('entrance/events', [EventSelectorController::class, 'index'])->name('entrance.events');
    Route::post('entrance/events/select', [EventSelectorController::class, 'select'])->name('entrance.events.select');
    Route::delete('entrance/events/select', [EventSelectorController::class, 'clear'])->name('entrance.events.clear');

    // Entrance API (browser-called, needs session auth)
    Route::prefix('api/entrance')->group(function () {
        Route::post('/validate', [EntranceController::class, 'validate'])->name('api.entrance.validate');
        Route::post('/checkin', [EntranceController::class, 'checkin'])->name('api.entrance.checkin');
        Route::post('/verify-checkin', [EntranceController::class, 'verifyCheckin'])->name('api.entrance.verify-checkin');
        Route::post('/confirm-payment', PaymentController::class)->name('api.entrance.confirm-payment');
        Route::post('/override', OverrideController::class)->middleware('entrance.role:moderator')->name('api.entrance.override');
        Route::get('/lookup', LookupController::class)->name('api.entrance.lookup');
    });
});

require __DIR__.'/settings.php';

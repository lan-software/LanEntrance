<?php

use App\Http\Controllers\Auth\LanCoreAuthController;
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
});

require __DIR__.'/settings.php';

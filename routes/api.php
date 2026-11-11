<?php

use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\BusinessController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TokenController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public JSON API (v1)
|--------------------------------------------------------------------------
|
| Bearer-token authentication: exchange email + password for a token via
| POST /api/v1/tokens, then send "Authorization: Bearer <token>" on every
| call. Only the sha-256 of each token is stored server-side.
|
| Full reference with curl examples: docs/API.md
|
*/

Route::post('v1/tokens', [TokenController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('api.tokens.store');

Route::middleware('api-token')->prefix('v1')->group(function () {
    Route::get('me', [ProfileController::class, 'show'])->name('api.me.show');
    Route::get('businesses', [BusinessController::class, 'index'])->name('api.businesses.index');
    Route::get('appointments', [AppointmentController::class, 'index'])->name('api.appointments.index');
    Route::delete('tokens/{apiToken}', [TokenController::class, 'destroy'])->name('api.tokens.destroy');
});

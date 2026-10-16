<?php

use App\Http\Controllers\Api\AcarsController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| ACARS API Routes
|--------------------------------------------------------------------------
|
| These routes are consumed by the SKYOS ACARS desktop client
| (Windows / Linux / macOS). Authentication is via a Bearer token
| issued by the /api/acars/login endpoint.
|
*/

Route::prefix('acars')->name('acars.')->group(function () {
    Route::post('/login', [AcarsController::class, 'login'])->name('login')->middleware('throttle:5,1');

    Route::middleware('api.token')->group(function () {
        Route::get('/flights', [AcarsController::class, 'flights'])->name('flights');
        Route::get('/flights/{flight}', [AcarsController::class, 'flight'])->name('flights.show');
        Route::post('/flights/{flight}/event', [AcarsController::class, 'event'])->name('flights.event');
        Route::post('/pireps', [AcarsController::class, 'storePirep'])->name('pireps.store');
    });
});

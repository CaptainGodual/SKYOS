<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CrewCenterController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/', [CrewCenterController::class, 'index'])->name('crew-center.index');
    Route::post('/flights/{flight}/book', [CrewCenterController::class, 'book'])->name('flights.book');
    Route::post('/flights/{flight}/events', [CrewCenterController::class, 'dispatchFlightEvent'])->name('flights.events.store');
    Route::post('/pireps', [CrewCenterController::class, 'storePirep'])->name('pireps.store');
    Route::post('/messages', [CrewCenterController::class, 'storeMessage'])->name('messages.store');
});

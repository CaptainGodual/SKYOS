<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AirportController;
use App\Http\Controllers\Admin\FlightController;
use App\Http\Controllers\Admin\PirepController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CrewCenterController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'approved'])->group(function () {
    Route::get('/', [CrewCenterController::class, 'index'])->name('crew-center.index');
    Route::post('/flights/{flight}/book', [CrewCenterController::class, 'book'])->name('flights.book');
    Route::post('/flights/{flight}/events', [CrewCenterController::class, 'dispatchFlightEvent'])->name('flights.events.store');
    Route::post('/pireps', [CrewCenterController::class, 'storePirep'])->name('pireps.store');
    Route::post('/messages', [CrewCenterController::class, 'storeMessage'])->name('messages.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::get('/flights', [FlightController::class, 'index'])->name('flights.index');
        Route::get('/flights/create', [FlightController::class, 'create'])->name('flights.create');
        Route::post('/flights', [FlightController::class, 'store'])->name('flights.store');
        Route::get('/flights/{flight}/edit', [FlightController::class, 'edit'])->name('flights.edit');
        Route::put('/flights/{flight}', [FlightController::class, 'update'])->name('flights.update');
        Route::delete('/flights/{flight}', [FlightController::class, 'destroy'])->name('flights.destroy');

        Route::get('/pireps', [PirepController::class, 'index'])->name('pireps.index');
        Route::get('/pireps/{pirep}', [PirepController::class, 'show'])->name('pireps.show');
        Route::put('/pireps/{pirep}', [PirepController::class, 'update'])->name('pireps.update');
        Route::delete('/pireps/{pirep}', [PirepController::class, 'destroy'])->name('pireps.destroy');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::put('/users/{user}/approve', [UserController::class, 'approve'])->name('users.approve');
        Route::put('/users/{user}/reject', [UserController::class, 'reject'])->name('users.reject');

        Route::get('/airports', [AirportController::class, 'index'])->name('airports.index');
        Route::get('/airports/import', [AirportController::class, 'import'])->name('airports.import');
        Route::post('/airports/import', [AirportController::class, 'performImport'])->name('airports.import.store');
        Route::put('/airports/{airport}/hub', [AirportController::class, 'toggleHub'])->name('airports.toggle-hub');
    });
});

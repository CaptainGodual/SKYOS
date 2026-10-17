<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AirportController;
use App\Http\Controllers\Admin\DownloadController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\FinanceController;
use App\Http\Controllers\Admin\FlightController;
use App\Http\Controllers\Admin\PirepController;
use App\Http\Controllers\Admin\TourController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\CrewCenterController;
use App\Http\Controllers\DownloadController as PublicDownloadController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TourCenterController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:6,1');

    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store')->middleware('throttle:10,1');
});

Route::post('/logout', [LoginController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'approved'])->group(function () {
    Route::get('/', [CrewCenterController::class, 'index'])->name('crew-center.index');
    Route::post('/flights/{flight}/book', [CrewCenterController::class, 'book'])->name('flights.book')->middleware('throttle:30,1');
    Route::post('/flights/{flight}/events', [CrewCenterController::class, 'dispatchFlightEvent'])->name('flights.events.store')->middleware('throttle:30,1');
    Route::post('/pireps', [CrewCenterController::class, 'storePirep'])->name('pireps.store')->middleware('throttle:12,1');

    Route::get('/messages', [MessageController::class, 'index'])->name('messages.index');
    Route::post('/messages', [MessageController::class, 'store'])->name('messages.store')->middleware('throttle:10,1');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('/tours', [TourCenterController::class, 'index'])->name('tours.index');
    Route::get('/tours/my', [TourCenterController::class, 'myTours'])->name('tours.my');
    Route::get('/tours/{tour}', [TourCenterController::class, 'show'])->name('tours.show');
    Route::post('/tours/{tour}/book', [TourCenterController::class, 'book'])->name('tours.book')->middleware('throttle:10,1');
    Route::post('/tours/bookings/{booking}/cancel', [TourCenterController::class, 'cancel'])->name('tours.cancel')->middleware('throttle:10,1');

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
        Route::get('/airports/search', [AirportController::class, 'search'])->name('airports.search')->middleware('throttle:60,1');

        Route::get('/downloads', [DownloadController::class, 'index'])->name('downloads.index');
        Route::get('/downloads/create', [DownloadController::class, 'create'])->name('downloads.create');
        Route::post('/downloads', [DownloadController::class, 'store'])->name('downloads.store');
        Route::get('/downloads/{download}/edit', [DownloadController::class, 'edit'])->name('downloads.edit');
        Route::put('/downloads/{download}', [DownloadController::class, 'update'])->name('downloads.update');
        Route::delete('/downloads/{download}', [DownloadController::class, 'destroy'])->name('downloads.destroy');

        Route::get('/tours', [TourController::class, 'index'])->name('tours.index');
        Route::get('/tours/create', [TourController::class, 'create'])->name('tours.create');
        Route::post('/tours', [TourController::class, 'store'])->name('tours.store');
        Route::get('/tours/{tour}/edit', [TourController::class, 'edit'])->name('tours.edit');
        Route::put('/tours/{tour}', [TourController::class, 'update'])->name('tours.update');
        Route::delete('/tours/{tour}', [TourController::class, 'destroy'])->name('tours.destroy');
        Route::post('/tours/{tour}/legs', [TourController::class, 'addLeg'])->name('tours.legs.store');
        Route::delete('/tours/{tour}/legs/{leg}', [TourController::class, 'removeLeg'])->name('tours.legs.destroy');

        Route::get('/finance', [FinanceController::class, 'index'])->name('finance.index');

        Route::get('/expenses', [ExpenseController::class, 'index'])->name('expenses.index');
        Route::get('/expenses/create', [ExpenseController::class, 'create'])->name('expenses.create');
        Route::post('/expenses', [ExpenseController::class, 'store'])->name('expenses.store');
        Route::get('/expenses/{expense}/edit', [ExpenseController::class, 'edit'])->name('expenses.edit');
        Route::put('/expenses/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
        Route::delete('/expenses/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    });

    Route::get('/downloads/{download}', [PublicDownloadController::class, 'download'])->name('downloads.download');
});

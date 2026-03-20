<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::get('/', [BookingController::class, 'index'])->name('booking.index');
Route::post('/bookings/draft', [BookingController::class, 'draft'])->name('booking.draft');
Route::post('/bookings/{booking}/claim', [BookingController::class, 'claim'])->name('booking.claim');
Route::post('/bookings/{booking}/confirm', [BookingController::class, 'confirm'])->name('booking.confirm');
Route::post('/bookings/{booking}/retry', [BookingController::class, 'retry'])->name('booking.retry')->middleware('auth');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';

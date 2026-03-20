<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::get('/bookings/{booking}/status', [BookingController::class, 'status'])
    ->name('booking.status')
    ->middleware('web');

<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

// Guest & User Routes
Route::get('/', [BookingController::class, 'index'])->name('home');
Route::post('/book', [BookingController::class, 'store'])->name('booking.store');
Route::get('/booking/success/{id}', [BookingController::class, 'success'])->name('booking.success');

// Auth Routes
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/admin', [BookingController::class, 'admin'])->name('admin');
    Route::get('/admin/stream', [BookingController::class, 'stream'])->name('admin.stream');
    Route::post('/admin/start/{id}', [BookingController::class, 'start'])->name('admin.start');
    Route::post('/admin/cancel/{id}', [BookingController::class, 'cancel'])->name('admin.cancel');
    Route::post('/admin/complete/{id}', [BookingController::class, 'complete'])->name('admin.complete');
    Route::put('/admin/reschedule/{id}', [BookingController::class, 'reschedule'])->name('admin.reschedule');
    Route::post('/admin/settings', [BookingController::class, 'updateSettings'])->name('admin.settings');
    Route::get('/admin/available-slots', [BookingController::class, 'availableSlots'])->name('admin.available-slots');
});
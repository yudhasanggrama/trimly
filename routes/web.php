<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

// ── Public / Customer ────────────────────────────────────
Route::get('/', [BookingController::class, 'index'])->name('home');
Route::post('/book', [BookingController::class, 'store'])->name('booking.store');

// ── Auth ─────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ── Admin ─────────────────────────────────────────────────
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/',                      [AdminController::class, 'index'])->name('index');
    Route::post('/start/{id}',           [AdminController::class, 'start'])->name('start');
    Route::post('/cancel/{id}',          [AdminController::class, 'cancel'])->name('cancel');
    Route::post('/complete/{id}',        [AdminController::class, 'complete'])->name('complete');
    Route::put('/reschedule/{id}',       [AdminController::class, 'reschedule'])->name('reschedule');
    Route::post('/settings',             [AdminController::class, 'updateSettings'])->name('settings');
    Route::get('/available-slots',       [AdminController::class, 'availableSlots'])->name('available-slots');
});
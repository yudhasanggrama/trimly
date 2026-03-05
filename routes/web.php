<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

// ── Public / Customer ────────────────────────────────────
Route::get('/', [BookingController::class, 'index'])->name('home');
Route::post('/book', [BookingController::class, 'store'])->name('booking.store');

// ── Auth ─────────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login'])->name('login');
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// ── Admin ─────────────────────────────────────────────────

Route::middleware(['auth', AdminMiddleware::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/',                      [AdminController::class, 'index'])->name('index');
    Route::get('/live-data',             [AdminController::class, 'liveData'])->name('live-data'); // ← tambah ini
    Route::post('/start/{id}',           [AdminController::class, 'start'])->name('start');
    Route::post('/cancel/{id}',          [AdminController::class, 'cancel'])->name('cancel');
    Route::post('/complete/{id}',        [AdminController::class, 'complete'])->name('complete');
    Route::put('/reschedule/{id}',       [AdminController::class, 'reschedule'])->name('reschedule');
    Route::post('/settings',             [AdminController::class, 'updateSettings'])->name('settings');
    Route::get('/available-slots',       [AdminController::class, 'availableSlots'])->name('available-slots');
});

Route::get('/test-mail', function () {
    try {
        Mail::raw('Test email', fn($m) => $m->to('raditbrian04@gmail.com')->subject('Test Trimly'));
        return 'Mail sent!';
    } catch (\Exception $e) {
        return $e->getMessage();
    }
});

Route::get('/flush-queue', function () {
    Artisan::call('queue:flush');
    Artisan::call('queue:clear', ['connection' => 'database']);
    return 'Queue flushed!';
});
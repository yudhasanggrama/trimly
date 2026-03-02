<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
// Guest & User Routes
Route::get('/', [BookingController::class, 'index'])->name('home');
Route::post('/book', [BookingController::class, 'store'])->name('booking.store');
Route::post('/admin/complete/{id}', [BookingController::class, 'complete'])
    ->name('admin.complete');

// Auth Routes
Route::get('/login', function() {
    return redirect('/')->with('openLoginModal', true);
})->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register'])->name('register');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Admin Routes (Hanya untuk Role Admin)
Route::middleware(['auth'])->group(function () {
    Route::get('/admin', [BookingController::class, 'admin'])->name('admin');
    Route::post('/admin/start/{id}', [BookingController::class, 'start'])->name('admin.start');
    Route::post('/admin/cancel/{id}', [BookingController::class, 'cancel'])->name('admin.cancel');
});
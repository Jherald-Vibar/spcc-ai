<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.store');

    Route::get('/register', [RegisterController::class, 'create'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('register.store');

    Route::get('/register/verify', [OtpController::class, 'create'])->name('otp.show');
    Route::post('/register/verify', [OtpController::class, 'store'])
        ->middleware('throttle:10,1')
        ->name('otp.verify');
    Route::post('/register/resend', [OtpController::class, 'resend'])
        ->middleware('throttle:6,10')
        ->name('otp.resend');
    Route::get('/register/complete', [OtpController::class, 'complete'])->name('register.complete');
});


Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

});

require __DIR__.'/student.php';
require __DIR__.'/teacher.php';
require __DIR__.'/admin.php';
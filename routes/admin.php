<?php

use App\Http\Controllers\AdminController;
use Illuminate\Support\Facades\Route;

/*
 | Admin routes. Load from routes/web.php:   require __DIR__.'/admin.php';
 | Add your role middleware (e.g. 'role:admin') to the array below if you have one.
 */
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');

    Route::get('/lessons', [AdminController::class, 'lessons'])->name('lessons');
    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
});

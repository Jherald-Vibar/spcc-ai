<?php

use App\Http\Controllers\StudentController;
use Illuminate\Support\Facades\Route;

/*
 | Student routes.
 | Load this file from routes/web.php:   require __DIR__.'/student.php';
 | If you already have a role middleware (e.g. 'role:student'), add it to the array below.
 */
Route::middleware(['auth'])->prefix('student')->name('student.')->group(function () {
    Route::get('/', [StudentController::class, 'dashboard'])->name('dashboard');

    Route::get('/lessons', [StudentController::class, 'lessons'])->name('lessons');
    Route::get('/lessons/{subject}/week/{week}', [StudentController::class, 'showLesson'])
        ->whereNumber('week')
        ->name('lessons.show');

    Route::get('/profile', [StudentController::class, 'profile'])->name('profile');
    Route::get('/settings', [StudentController::class, 'settings'])->name('settings');
});

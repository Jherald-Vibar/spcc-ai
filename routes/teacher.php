<?php

use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

/*
 | Teacher routes. Load from routes/web.php:   require __DIR__.'/teacher.php';
 | Add your role middleware (e.g. 'role:teacher') to the array below if you have one.
 */
Route::middleware(['auth'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/', [TeacherController::class, 'dashboard'])->name('dashboard');

    Route::get('/subjects', [TeacherController::class, 'subjects'])->name('subjects');
    Route::get('/subjects/{subject}/lessons/create', [TeacherController::class, 'createLesson'])->name('lessons.create');
    Route::post('/subjects/{subject}/lessons', [TeacherController::class, 'storeLesson'])->name('lessons.store');

    Route::get('/profile', [TeacherController::class, 'profile'])->name('profile');
    Route::get('/settings', [TeacherController::class, 'settings'])->name('settings');
});

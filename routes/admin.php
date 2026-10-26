<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminLessonController;
use App\Http\Controllers\AdminSubjectController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureRole::class . ':admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

    // users (students / teachers / administrators)
    Route::get('/users', [AdminController::class, 'users'])->name('users');
    Route::post('/users', [AdminController::class, 'storeUser'])->name('users.store');
    Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
    Route::patch('/users/{user}/status', [AdminController::class, 'toggleUser'])->name('users.toggle');
    Route::post('/users/{user}/reset-password', [AdminController::class, 'resetPassword'])->name('users.reset');
    Route::delete('/users/{user}', [AdminController::class, 'destroyUser'])->name('users.destroy');

    // subjects (create, assign a teacher)
    Route::get('/subjects', [AdminSubjectController::class, 'index'])->name('subjects');
    Route::post('/subjects', [AdminSubjectController::class, 'store'])->name('subjects.store');
    Route::patch('/subjects/{subject}', [AdminSubjectController::class, 'update'])->name('subjects.update');
    Route::delete('/subjects/{subject}', [AdminSubjectController::class, 'destroy'])->name('subjects.destroy');

    // lesson moderation
    Route::get('/lessons', [AdminLessonController::class, 'index'])->name('lessons');
    Route::get('/lessons/{lesson}', [AdminLessonController::class, 'show'])->name('lessons.show');
    Route::post('/lessons/{lesson}/publish', [AdminLessonController::class, 'publish'])->name('lessons.publish');
    Route::post('/lessons/{lesson}/unpublish', [AdminLessonController::class, 'unpublish'])->name('lessons.unpublish');
    Route::post('/lessons/{lesson}/dismiss-reports', [AdminLessonController::class, 'dismissReports'])->name('lessons.dismiss');
    Route::delete('/lessons/{lesson}', [AdminLessonController::class, 'destroy'])->name('lessons.destroy');
    Route::get('/files/{file}', [AdminLessonController::class, 'downloadFile'])->name('files.download');

    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
});

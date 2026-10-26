<?php

use App\Http\Controllers\LessonChatController;
use App\Http\Controllers\StudentController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

/*
 | Student routes.
 | Loaded from routes/web.php:   require __DIR__.'/student.php';
 */
Route::middleware(['auth', EnsureRole::class . ':student'])->prefix('student')->name('student.')->group(function () {
    Route::get('/', [StudentController::class, 'dashboard'])->name('dashboard');

    Route::get('/lessons', [StudentController::class, 'lessons'])->name('lessons');
    Route::get('/lessons/{subject}/week/{week}', [StudentController::class, 'showLesson'])
        ->whereNumber('week')
        ->name('lessons.show');

    // AI chat (answers only from the lecture files, with citations)
    Route::post('/lessons/{subject}/week/{week}/chat', LessonChatController::class)
        ->whereNumber('week')
        ->middleware('throttle:20,1')
        ->name('lessons.chat');
    Route::get('/files/{file}', [StudentController::class, 'downloadFile'])
        ->whereNumber('file')
        ->name('files.download');

    Route::get('/profile', [StudentController::class, 'profile'])->name('profile');
    Route::get('/settings', [StudentController::class, 'settings'])->name('settings');
});

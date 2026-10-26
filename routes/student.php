<?php

use App\Http\Controllers\ForumController;
use App\Http\Controllers\LessonChatController;
use App\Http\Controllers\LessonQuizController;
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
    // forum (discussions and replies under a lesson)
    Route::post('/lessons/{subject}/week/{week}/forum', [ForumController::class, 'store'])
        ->whereNumber('week')
        ->middleware('throttle:10,1')
        ->name('forum.store');
    Route::delete('/forum/{post}', [ForumController::class, 'destroy'])
        ->whereNumber('post')
        ->name('forum.destroy');

    // reviewer: answers are graded on the server
    Route::post('/lessons/{subject}/week/{week}/quiz', [LessonQuizController::class, 'submit'])
        ->whereNumber('week')
        ->middleware('throttle:30,1')
        ->name('quiz.submit');

    Route::get('/files/{file}', [StudentController::class, 'downloadFile'])
        ->whereNumber('file')
        ->name('files.download');

    Route::get('/profile', [StudentController::class, 'profile'])->name('profile');
    Route::get('/settings', [StudentController::class, 'settings'])->name('settings');
});

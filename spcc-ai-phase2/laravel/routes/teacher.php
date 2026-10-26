<?php

use App\Http\Controllers\TeacherController;
use App\Http\Controllers\TeacherFileController;
use App\Http\Middleware\EnsureRole;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', EnsureRole::class . ':teacher'])->prefix('teacher')->name('teacher.')->group(function () {
    Route::get('/', [TeacherController::class, 'dashboard'])->name('dashboard');
    Route::get('/subjects', [TeacherController::class, 'subjects'])->name('subjects');

    Route::prefix('subjects/{subject}/lessons')->group(function () {
        Route::get('/create', [TeacherController::class, 'createLesson'])->name('lessons.create');
        Route::post('/', [TeacherController::class, 'storeLesson'])->name('lessons.store');

        Route::get('/{week}', [TeacherController::class, 'showLesson'])->whereNumber('week')->name('lessons.show');
        Route::get('/{week}/edit', [TeacherController::class, 'editLesson'])->whereNumber('week')->name('lessons.edit');
        Route::put('/{week}', [TeacherController::class, 'updateLesson'])->whereNumber('week')->name('lessons.update');
        Route::post('/{week}/publish', [TeacherController::class, 'togglePublish'])->whereNumber('week')->name('lessons.publish');
        Route::delete('/{week}', [TeacherController::class, 'destroyLesson'])->whereNumber('week')->name('lessons.destroy');
    });

    Route::get('/files/{file}', [TeacherController::class, 'downloadFile'])->whereNumber('file')->name('files.download');
    Route::post('/files/{file}/reprocess', [TeacherFileController::class, 'reprocess'])->whereNumber('file')->name('files.reprocess');

    Route::get('/profile', [TeacherController::class, 'profile'])->name('profile');
    Route::get('/settings', [TeacherController::class, 'settings'])->name('settings');
});

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            // none | generating | ready | failed   (the AI-written reviewer for this lesson)
            $table->string('quiz_status', 12)->default('none')->after('video_requested');
            $table->string('quiz_error', 300)->nullable()->after('quiz_status');
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position')->default(0);
            $table->text('question');
            $table->json('options');                         // ["...", "...", "...", "..."]
            $table->unsignedTinyInteger('answer_index');     // never sent to the browser before the student submits
            $table->text('explanation')->nullable();
            $table->timestamps();

            $table->index(['lesson_id', 'position']);
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('score');
            $table->unsignedTinyInteger('total');
            $table->json('answers')->nullable();
            $table->timestamps();

            $table->index(['lesson_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_questions');
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn(['quiz_status', 'quiz_error']);
        });
    }
};

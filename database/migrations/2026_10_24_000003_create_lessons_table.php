<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('week');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('published_at')->nullable();   // null = draft
            $table->string('video_path')->nullable();        // filled by the AI video phase
            $table->string('duration', 10)->nullable();      // e.g. 8:45
            $table->timestamps();

            $table->unique(['subject_id', 'week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};

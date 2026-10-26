<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('scope', 10)->default('lesson');      // lesson | subject
            $table->text('question');
            $table->text('answer');
            $table->boolean('found')->default(false);            // false = "not in the lecture materials"
            $table->decimal('top_score', 5, 3)->nullable();      // best similarity, for tuning the threshold
            $table->json('sources')->nullable();
            $table->timestamps();

            $table->index(['lesson_id', 'found']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};

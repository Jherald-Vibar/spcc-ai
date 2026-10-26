<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forum_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('forum_posts')->cascadeOnDelete(); // null = a discussion, set = a reply
            $table->text('body');
            $table->timestamps();

            $table->index(['lesson_id', 'parent_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forum_posts');
    }
};

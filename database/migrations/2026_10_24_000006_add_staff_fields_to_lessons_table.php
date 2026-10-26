<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('subject_id')->constrained('users')->nullOnDelete();
            $table->text('ai_prompt')->nullable()->after('description');
            $table->boolean('video_requested')->default(false)->after('ai_prompt');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn(['ai_prompt', 'video_requested']);
        });
    }
};

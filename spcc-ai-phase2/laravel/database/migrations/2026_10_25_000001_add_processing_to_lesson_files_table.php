<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lesson_files', function (Blueprint $table) {
            // pending | processing | ready | failed
            $table->string('processing_status', 15)->default('pending')->after('bytes')->index();
            $table->string('processing_error', 500)->nullable()->after('processing_status');
            $table->unsignedInteger('chunk_count')->default(0)->after('processing_error');
            $table->timestamp('processed_at')->nullable()->after('chunk_count');
        });
    }

    public function down(): void
    {
        Schema::table('lesson_files', function (Blueprint $table) {
            $table->dropColumn(['processing_status', 'processing_error', 'chunk_count', 'processed_at']);
        });
    }
};

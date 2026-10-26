<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();   // who reported it
            $table->string('reason', 500);
            $table->timestamp('resolved_at')->nullable();                              // null = still open
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_reports');
    }
};

<?php

namespace App\Console\Commands;

use App\Jobs\ProcessLessonFile;
use App\Models\LessonFile;
use Illuminate\Console\Command;

class ReindexLessonFiles extends Command
{
    protected $signature = 'ai:reindex {--all : Re-process every file, not only the ones that are not ready}';

    protected $description = 'Send lecture files to the AI service (files uploaded before Phase 2, or ones that failed)';

    public function handle(): int
    {
        $query = LessonFile::query();

        if (! $this->option('all')) {
            $query->where('processing_status', '!=', LessonFile::STATUS_READY);
        }

        $count = 0;
        $query->each(function (LessonFile $file) use (&$count) {
            $file->update(['processing_status' => LessonFile::STATUS_PENDING, 'processing_error' => null]);
            ProcessLessonFile::dispatch($file->id);
            $count++;
        });

        $this->info("Queued {$count} file(s). Make sure `php artisan queue:work` is running.");

        return self::SUCCESS;
    }
}

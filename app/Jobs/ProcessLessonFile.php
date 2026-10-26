<?php

namespace App\Jobs;

use App\Models\LessonFile;
use App\Services\AiClient;
use App\Services\AiServiceException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/** Extract + chunk + embed one uploaded lecture file (the heavy work happens in the AI service). */
class ProcessLessonFile implements ShouldQueue
{
    use InteractsWithQueue, Queueable;

    public int $tries = 3;
    public int $timeout = 900;

    public function __construct(public int $fileId)
    {
    }

    /** Seconds to wait between attempts (the AI service may still be starting). */
    public function backoff(): array
    {
        return [30, 120];
    }

    public function handle(AiClient $ai): void
    {
        $file = LessonFile::with('lesson.subject')->find($this->fileId);

        if (! $file) {
            return; // deleted before it was processed
        }

        $file->update(['processing_status' => LessonFile::STATUS_PROCESSING, 'processing_error' => null]);

        $path = Storage::disk('local')->path($file->path);

        try {
            $result = $ai->ingest($file, $path);
        } catch (AiServiceException $e) {
            if (! $e->retryable) {
                $this->fail($e); // no point retrying: failed() records the reason for the teacher

                return;
            }

            throw $e;
        }

        // The file may have been removed while it was being processed: clean the vectors up again.
        if (! LessonFile::whereKey($file->id)->exists()) {
            DeleteFileVectors::dispatch($file->id);

            return;
        }

        $file->update([
            'processing_status' => LessonFile::STATUS_READY,
            'processing_error'  => null,
            'chunk_count'       => (int) ($result['chunks'] ?? 0),
            'processed_at'      => now(),
        ]);

        // new material is ready: (re)write the lesson's reviewer questions from all of its files
        GenerateLessonQuiz::dispatch($file->lesson_id);
    }

    public function failed(Throwable $e): void
    {
        LessonFile::whereKey($this->fileId)->update([
            'processing_status' => LessonFile::STATUS_FAILED,
            'processing_error'  => Str::limit($e->getMessage(), 480, ''),
        ]);
    }
}

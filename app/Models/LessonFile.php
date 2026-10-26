<?php

namespace App\Models;

use App\Jobs\DeleteFileVectors;
use App\Jobs\GenerateLessonQuiz;
use App\Jobs\ProcessLessonFile;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Number;

class LessonFile extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_READY = 'ready';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'lesson_id', 'name', 'path', 'extension', 'bytes',
        'processing_status', 'processing_error', 'chunk_count', 'processed_at',
    ];

    protected function casts(): array
    {
        return ['processed_at' => 'datetime', 'chunk_count' => 'integer'];
    }

    protected static function booted(): void
    {
        // Every new upload is sent to the AI service (after the DB transaction commits).
        static::created(function (LessonFile $file) {
            ProcessLessonFile::dispatch($file->id)->afterCommit();
        });

        // Removing a file (or its lesson) also removes its vectors, so the AI can no longer quote it.
        static::deleted(function (LessonFile $file) {
            DeleteFileVectors::dispatch($file->id)->afterCommit();
            // the reviewer was written from the old materials: rebuild it from what is left
            GenerateLessonQuiz::dispatch($file->lesson_id)->afterCommit();
        });
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function isReady(): bool
    {
        return $this->processing_status === self::STATUS_READY;
    }

    /** $file['type'] in the materials list, e.g. PDF. */
    public function getTypeAttribute(): string
    {
        return strtoupper($this->extension);
    }

    /** $file['size'] in the materials list, e.g. 1.2 MB. */
    public function getSizeAttribute(): string
    {
        return Number::fileSize((int) $this->bytes, 1);
    }
}

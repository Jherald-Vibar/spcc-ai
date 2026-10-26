<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Lesson extends Model
{
    protected $fillable = [
        'subject_id', 'created_by', 'week', 'title', 'description', 'ai_prompt', 'video_requested',
        'published_at', 'video_path', 'youtube_url', 'duration', 'quiz_status', 'quiz_error',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'week' => 'integer',
            'video_requested' => 'boolean',
        ];
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(LessonFile::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function forumPosts(): HasMany
    {
        return $this->hasMany(ForumPost::class);
    }

    public function quizQuestions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('position');
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(LessonReport::class);
    }

    /** Only lessons students may see. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /** Drafts: not visible to students yet. */
    public function scopeDraft(Builder $query): Builder
    {
        return $query->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '>', now()));
    }

    public function scopeReported(Builder $query): Builder
    {
        return $query->whereHas('reports', fn ($q) => $q->whereNull('resolved_at'));
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->lte(now());
    }

    /** published | draft | reported, for the staff screens (x-badge). */
    public function getDisplayStatusAttribute(): string
    {
        $open = $this->open_reports_count ?? $this->reports()->whereNull('resolved_at')->count();

        if ($open > 0) {
            return 'reported';
        }

        return $this->isPublished() ? 'published' : 'draft';
    }

    /** Used by the student views as $lesson['video_url']. */
    public function getVideoUrlAttribute(): ?string
    {
        return $this->video_path ? Storage::disk('public')->url($this->video_path) : null;
    }

    /**
     * Extract the 11-character video id from a YouTube link (watch, youtu.be, shorts, embed or live URLs).
     * Returns null for anything that is not a YouTube video link.
     */
    public static function youtubeId(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '' || ! preg_match('#^https?://#i', $url)) {
            return null;
        }

        $parts = parse_url($url);
        $host = strtolower($parts['host'] ?? '');
        $host = preg_replace('/^(www\.|m\.)/', '', $host);
        $path = $parts['path'] ?? '';
        $id = null;

        if ($host === 'youtu.be') {
            $id = ltrim($path, '/');
        } elseif (in_array($host, ['youtube.com', 'youtube-nocookie.com'], true)) {
            if (preg_match('#^/(?:embed|shorts|live|v)/([^/?]+)#', $path, $m)) {
                $id = $m[1];
            } elseif ($path === '/watch') {
                parse_str($parts['query'] ?? '', $query);
                $id = is_string($query['v'] ?? null) ? $query['v'] : null;
            }
        }

        return $id !== null && preg_match('/^[A-Za-z0-9_-]{11}$/', $id) ? $id : null;
    }

    /** Privacy-friendly embed URL for the student player, or null when no valid YouTube link is saved. */
    public function getYoutubeEmbedUrlAttribute(): ?string
    {
        $id = self::youtubeId($this->youtube_url);

        return $id ? "https://www.youtube-nocookie.com/embed/{$id}?rel=0" : null;
    }

    /**
     * Attach the given student's progress as $lesson->status
     * (completed | in_progress | upcoming), which the student views and status badge expect.
     */
    public static function withStatusFor(iterable $lessons, User $student): void
    {
        $lessons = collect($lessons);
        $byLesson = LessonProgress::where('user_id', $student->id)
            ->whereIn('lesson_id', $lessons->pluck('id'))
            ->pluck('status', 'lesson_id');

        foreach ($lessons as $lesson) {
            $lesson->setAttribute('status', $byLesson[$lesson->id] ?? 'upcoming');
        }
    }

    /** Delete the lesson and its files from the private disk. */
    public function deleteWithFiles(): void
    {
        foreach ($this->files as $file) {
            Storage::disk('local')->delete($file->path);
            $file->delete();   // fires LessonFile::deleted, which also removes the file's AI vectors
        }

        $this->delete();   // progress / reports / chat rows go with it (cascade)
    }
}

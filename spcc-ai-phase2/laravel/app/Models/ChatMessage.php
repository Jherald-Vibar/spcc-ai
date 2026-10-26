<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One question a student asked the AI chat, with the answer and its sources (kept for analytics). */
class ChatMessage extends Model
{
    protected $fillable = ['user_id', 'lesson_id', 'scope', 'question', 'answer', 'found', 'top_score', 'sources'];

    protected function casts(): array
    {
        return [
            'found'     => 'boolean',
            'top_score' => 'float',
            'sources'   => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}

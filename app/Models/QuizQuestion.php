<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    protected $fillable = ['lesson_id', 'position', 'question', 'options', 'answer_index', 'explanation'];

    protected function casts(): array
    {
        return ['options' => 'array', 'answer_index' => 'integer', 'position' => 'integer'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
}

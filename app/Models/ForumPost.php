<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** A discussion (parent_id null) or a reply (parent_id set) under one lesson. */
class ForumPost extends Model
{
    protected $fillable = ['lesson_id', 'user_id', 'parent_id', 'body'];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ForumPost::class, 'parent_id')->orderBy('created_at');
    }
}

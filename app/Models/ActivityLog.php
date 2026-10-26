<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = ['user_id', 'type', 'title', 'detail'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Write one entry for the admin dashboard ("Recent Activities") and usage chart. */
    public static function record(string $type, string $title, ?string $detail = null, ?User $user = null): self
    {
        return static::create([
            'user_id' => ($user ?? auth()->user())?->id,
            'type' => $type,
            'title' => $title,
            'detail' => $detail,
        ]);
    }

    /** Icon and colours used by the dashboard list, by type. */
    public function style(): array
    {
        return match ($this->type) {
            'lesson_published' => ['book-open', 'bg-green-100 text-green-600'],
            'lesson_updated'   => ['document',  'bg-orange-100 text-orange-600'],
            'lesson_removed'   => ['document',  'bg-red-100 text-red-600'],
            'user_created'     => ['user',      'bg-blue-100 text-blue-600'],
            'report'           => ['chat',      'bg-violet-100 text-violet-600'],
            default            => ['check',     'bg-slate-100 text-slate-600'],
        };
    }
}

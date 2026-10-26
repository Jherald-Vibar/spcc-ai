<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subject extends Model
{
    public const YEARS = [
        1 => '1st Year',
        2 => '2nd Year',
        3 => '3rd Year',
        4 => '4th Year',
    ];

    protected $fillable = ['code', 'name', 'year', 'teacher_id'];

    protected function casts(): array
    {
        return ['year' => 'integer'];
    }

    /**
     * Subjects a student may open: their own year level only.
     * A student with no year level set yet (e.g. just self-registered) sees all years.
     */
    public function scopeVisibleTo(Builder $query, User $student): Builder
    {
        return $student->year_level
            ? $query->where('year', $student->year_level)
            : $query;
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('week');
    }
}

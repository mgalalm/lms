<?php

namespace App\Models;

use Database\Factories\LessonProjectFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The project assignment attached to a lesson (brief/title/required flag)
 * — distinct from ProjectSubmission, which is a student's attempt at it.
 */
class LessonProject extends Model
{
    /** @use HasFactory<LessonProjectFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lesson_id',
        'title',
        'brief',
        'is_required',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get every student attempt submitted against this project.
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(ProjectSubmission::class);
    }
}

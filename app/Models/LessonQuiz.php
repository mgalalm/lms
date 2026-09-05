<?php

namespace App\Models;

use App\Enums\LessonQuizStatus;
use Database\Factories\LessonQuizFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A lesson's student-facing quiz, generated from LessonSlideshow::slides_text
 * via QuizGenerationService — distinct from the course-level Quiz/QuizQuestion
 * pair, which is the teacher certification quiz gating who can teach a course.
 */
class LessonQuiz extends Model
{
    /** @use HasFactory<LessonQuizFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lesson_id',
        'locale',
        'generated_at',
        'source_text_hash',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'generated_at' => 'datetime',
            'status' => LessonQuizStatus::class,
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Scope to quizzes visible to students — see LessonQuizPolicy.
     *
     * @param  Builder<LessonQuiz>  $query
     * @return Builder<LessonQuiz>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', LessonQuizStatus::Published);
    }

    /**
     * Get this quiz's questions, in display order.
     */
    public function questions(): HasMany
    {
        return $this->hasMany(LessonQuizQuestion::class)->orderBy('sort_order');
    }
}

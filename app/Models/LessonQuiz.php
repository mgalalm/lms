<?php

namespace App\Models;

use App\Enums\LessonQuizStatus;
use App\Enums\SettingKey;
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
        'generation_count',
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
            'generation_count' => 'integer',
            'status' => LessonQuizStatus::class,
        ];
    }

    /**
     * Whether this quiz has any AI generations left, per the
     * quiz.max_generations setting. The count includes the first
     * generation and is never reset — the cap is permanent per quiz.
     * Admins can still edit questions by hand once it's reached.
     */
    public function hasGenerationsRemaining(): bool
    {
        return $this->generation_count < Setting::get(SettingKey::QuizMaxGenerations);
    }

    /**
     * Generations still available for this quiz, floored at zero.
     */
    public function generationsRemaining(): int
    {
        return max(0, (int) Setting::get(SettingKey::QuizMaxGenerations) - $this->generation_count);
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

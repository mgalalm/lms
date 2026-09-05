<?php

namespace App\Models;

use Database\Factories\TeacherLessonQuizAttemptFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One teacher's scored attempt at a lesson quiz, as a learner working
 * toward course certification. Unlike the student-facing attempt flow
 * (stateless — see Student\LessonQuizAttemptController), these are
 * persisted: the attempt cap and the completion gate both depend on
 * knowing what a teacher has already done.
 */
class TeacherLessonQuizAttempt extends Model
{
    /** @use HasFactory<TeacherLessonQuizAttemptFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'teacher_profile_id',
        'lesson_quiz_id',
        'score',
        'passed',
        'voided_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'score' => 'integer',
            'passed' => 'boolean',
            'voided_at' => 'datetime',
        ];
    }

    public function teacherProfile(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class);
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(LessonQuiz::class, 'lesson_quiz_id');
    }

    /**
     * Attempts that still count — an admin reset voids attempts rather
     * than deleting them, so every count/pass check must scope to these.
     *
     * @param  Builder<TeacherLessonQuizAttempt>  $query
     * @return Builder<TeacherLessonQuizAttempt>
     */
    public function scopeCounting(Builder $query): Builder
    {
        return $query->whereNull('voided_at');
    }
}

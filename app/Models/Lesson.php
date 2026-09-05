<?php

namespace App\Models;

use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'module_id',
        'title',
        'order',
        'reading_content',
    ];

    /**
     * Get the module this lesson belongs to.
     */
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    /**
     * Get the sessions scheduled for this lesson.
     */
    public function sessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    /**
     * Get this lesson's teaching video (curriculum content) — distinct from
     * a ClassSession's recap recording.
     */
    public function video(): HasOne
    {
        return $this->hasOne(LessonVideo::class);
    }

    /**
     * Get this lesson's teacher/admin-only prep material — never visible
     * to students. See LessonTutorGuidePolicy.
     */
    public function tutorGuide(): HasOne
    {
        return $this->hasOne(LessonTutorGuide::class);
    }

    /**
     * Get this lesson's admin-authored, PDF-backed slideshow.
     */
    public function slideshow(): HasOne
    {
        return $this->hasOne(LessonSlideshow::class);
    }

    /**
     * Get this lesson's project assignment, if one exists — not every
     * lesson has one.
     */
    public function project(): HasOne
    {
        return $this->hasOne(LessonProject::class);
    }

    /**
     * Get this lesson's student-facing quiz, generated from the
     * slideshow's slides_text. Distinct from Course::quiz(), the
     * teacher certification quiz.
     */
    public function quiz(): HasOne
    {
        return $this->hasOne(LessonQuiz::class);
    }

    /**
     * Teacher attempts at this lesson's quiz — surfaced in Filament so an
     * admin can reset a teacher who has used up their allowance.
     */
    public function quizAttempts(): HasManyThrough
    {
        return $this->hasManyThrough(
            TeacherLessonQuizAttempt::class,
            LessonQuiz::class,
            'lesson_id',
            'lesson_quiz_id',
        );
    }
}

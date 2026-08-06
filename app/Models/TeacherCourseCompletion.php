<?php

namespace App\Models;

use Database\Factories\TeacherCourseCompletionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherCourseCompletion extends Model
{
    /** @use HasFactory<TeacherCourseCompletionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'teacher_profile_id',
        'course_id',
        'completed_at',
        'quiz_passed',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'quiz_passed' => 'boolean',
        ];
    }

    /**
     * Get the teacher profile this completion record belongs to.
     */
    public function teacherProfile(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class);
    }

    /**
     * Get the course this completion record is for.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Whether this record represents an actual certification — completed
     * the course as a learner AND passed its quiz, not just started it.
     */
    public function isCertified(): bool
    {
        return $this->completed_at !== null && $this->quiz_passed;
    }
}

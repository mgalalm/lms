<?php

namespace App\Models;

use Database\Factories\TeacherProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TeacherProfile extends Model
{
    /** @use HasFactory<TeacherProfileFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'code',
        'photo_path',
        'rank',
        'starting_date',
        'languages',
        'faculty_of_graduation',
        'graduation_year',
        'employment_type',
        'tutor_team_id',
        'team_leader_name',
        'mentor_name',
        'bank_name',
        'bank_account_number',
        'zoom_user_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starting_date' => 'date',
            'languages' => 'array',
        ];
    }

    /**
     * Get the user this teacher profile belongs to.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get this teacher's course completion/certification records.
     */
    public function courseCompletions(): HasMany
    {
        return $this->hasMany(TeacherCourseCompletion::class);
    }

    /**
     * Whether this teacher has completed the given course themselves,
     * including passing its quiz — the certification gate for being
     * assigned to teach it.
     */
    public function isCertifiedForCourse(Course $course): bool
    {
        return $this->courseCompletions()
            ->where('course_id', $course->id)
            ->whereNotNull('completed_at')
            ->where('quiz_passed', true)
            ->exists();
    }
}

<?php

namespace App\Models;

use Database\Factories\TeacherModuleCompletionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A teacher's certification for one module: they completed its lessons as
 * a learner and passed the module quiz. This is the unit of certification
 * — see TeacherCourseCompletion for the course-level rollup derived from
 * these.
 */
class TeacherModuleCompletion extends Model
{
    /** @use HasFactory<TeacherModuleCompletionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'teacher_profile_id',
        'module_id',
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

    public function teacherProfile(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }
}

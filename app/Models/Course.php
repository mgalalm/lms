<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Course extends Model
{
    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'grade_band',
        'thumbnail_path',
        'sort_order',
    ];

    /**
     * Display label — a course IS a grade band in this model (no parallel
     * tracks within a grade), so this is a drop-in for any code that
     * wants a generic "name" rather than the raw grade_band field.
     */
    public function getNameAttribute(): string
    {
        return $this->grade_band;
    }

    /**
     * Get the modules belonging to this course.
     */
    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    /**
     * Get teacher certification/completion records for this course.
     */
    public function teacherCompletions(): HasMany
    {
        return $this->hasMany(TeacherCourseCompletion::class);
    }

    /**
     * Get all lessons in this course, across all of its modules.
     */
    public function lessons(): HasManyThrough
    {
        return $this->hasManyThrough(Lesson::class, Module::class);
    }

    /**
     * Get the certification quiz for this course, if configured.
     */
    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }
}

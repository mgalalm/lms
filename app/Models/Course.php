<?php

namespace App\Models;

use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'title',
        'grade_band',
        'thumbnail_path',
    ];

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
}

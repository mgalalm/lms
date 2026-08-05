<?php

namespace App\Models;

use Database\Factories\ModuleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Module extends Model
{
    /** @use HasFactory<ModuleFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'course_id',
        'title',
        'order',
    ];

    /**
     * Get the course this module belongs to.
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * Get the lessons belonging to this module.
     */
    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }
}

<?php

namespace App\Models;

use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
}

<?php

namespace App\Models;

use App\Enums\ProjectSubmissionStatus;
use Database\Factories\ProjectSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * One student's attempt at a LessonProject. A student may have several of
 * these over time for the same project (full attempt history, not
 * overwrite-in-place) — each with its own independent ProjectFeedback.
 */
class ProjectSubmission extends Model implements HasMedia
{
    /** @use HasFactory<ProjectSubmissionFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lesson_project_id',
        'student_id',
        'title',
        'description',
        'live_url',
        'status',
        'submitted_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ProjectSubmissionStatus::class,
            'submitted_at' => 'datetime',
        ];
    }

    public function lessonProject(): BelongsTo
    {
        return $this->belongsTo(LessonProject::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function feedback(): HasOne
    {
        return $this->hasOne(ProjectFeedback::class);
    }

    public function registerMediaCollections(): void
    {
        // Browser-facing project screenshots — CDN-backed Bunny disk,
        // multiple images allowed (no singleFile()), matching
        // LessonTutorGuide's 'intro-images' collection exactly.
        $this->addMediaCollection('screenshots')
            ->useDisk('bunny');
    }
}

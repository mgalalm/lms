<?php

namespace App\Models;

use Database\Factories\LessonSlideshowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Admin-authored slideshow (PowerPoint-style, PDF-backed) for a lesson.
 * Distinct from LessonVideo, LessonTutorGuide, and a ClassSession's recap.
 * No draft/published state (unlike LessonVideo) — file_path being set is
 * the only readiness signal, matching this model's minimal schema.
 */
class LessonSlideshow extends Model
{
    /** @use HasFactory<LessonSlideshowFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lesson_id',
        'locale',
        'file_path',
    ];

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Whether the PDF is ready to view — false while a PPTX upload is
     * still being converted.
     */
    public function isReady(): bool
    {
        return $this->file_path !== null;
    }
}

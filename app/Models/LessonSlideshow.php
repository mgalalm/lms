<?php

namespace App\Models;

use Database\Factories\LessonSlideshowFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Admin-authored slideshow for a lesson, embedded from Google Slides.
 * Distinct from LessonVideo, LessonTutorGuide, and a ClassSession's recap.
 * No draft/published state (unlike LessonVideo) — google_slides_id being
 * set is the only readiness signal, matching this model's minimal schema.
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
        'google_slides_url',
        'google_slides_id',
        'slides_text',
        'slides_text_updated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'slides_text_updated_at' => 'datetime',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Extract the presentation ID from a docs.google.com/presentation/d/{id}/...
     * URL. Returns null for anything that isn't a well-formed Google Slides
     * presentation URL, mirroring NotionSyncService::extractPageId()'s shape.
     */
    public static function extractPresentationId(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        if (! $host || ! preg_match('/(^|\.)docs\.google\.com$/', $host)) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! $path || ! preg_match('#^/presentation/d/([a-zA-Z0-9_-]+)#', $path, $matches)) {
            return null;
        }

        return $matches[1];
    }
}

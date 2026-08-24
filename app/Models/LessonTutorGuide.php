<?php

namespace App\Models;

use Database\Factories\LessonTutorGuideFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Teacher/admin-only lesson prep material — never visible to students
 * under any circumstance (see LessonTutorGuidePolicy). Distinct from
 * LessonVideo (curriculum teaching video) and a ClassSession's recap.
 */
class LessonTutorGuide extends Model implements HasMedia
{
    /** @use HasFactory<LessonTutorGuideFactory> */
    use HasFactory, InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lesson_id',
        'locale',
        'notion_page_id',
        'last_synced_at',
        'procedure_steps',
        'glossary_terms',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_synced_at' => 'datetime',
            'procedure_steps' => 'array',
            'glossary_terms' => 'array',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Whether the last Notion sync is more than a week old (or has never
     * happened) — surfaced as a warning in Filament, not enforced anywhere.
     */
    public function isSyncStale(): bool
    {
        return $this->last_synced_at === null || $this->last_synced_at->lt(now()->subDays(7));
    }

    public function registerMediaCollections(): void
    {
        // Intro text (markdown) — a server disk, never touched directly
        // by a browser, so no CDN/signed-URL need.
        $this->addMediaCollection('intro')
            ->useDisk('local')
            ->singleFile();

        // Images embedded within the intro markdown ARE browser-facing
        // once rendered inline, so they go to the CDN-backed Bunny disk.
        $this->addMediaCollection('intro-images')
            ->useDisk('bunny');
    }

    /**
     * Get the intro markdown's raw content, or null if none has been
     * saved yet. Media::getContent() doesn't exist — read back through
     * the disk the media collection is actually stored on instead.
     */
    public function introMarkdown(): ?string
    {
        $media = $this->getFirstMedia('intro');

        return $media ? Storage::disk($media->disk)->get($media->getPathRelativeToRoot()) : null;
    }

    /**
     * Replace the intro markdown, stored as the 'intro' media collection's
     * single file rather than a database column.
     */
    public function updateIntroMarkdown(string $markdown): Media
    {
        return $this
            ->addMediaFromString($markdown)
            ->usingFileName('intro.md')
            ->toMediaCollection('intro');
    }
}

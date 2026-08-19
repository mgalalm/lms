<?php

namespace App\Models;

use App\Enums\LessonVideoStatus;
use Database\Factories\LessonVideoFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonVideo extends Model
{
    /** @use HasFactory<LessonVideoFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lesson_id',
        'locale',
        'bunny_video_id',
        'status',
        'duration_seconds',
        'thumbnail_url',
        'uploaded_by',
        'published_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => LessonVideoStatus::class,
            'duration_seconds' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    /**
     * Get the lesson this video belongs to.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get the admin/teacher who uploaded this video.
     */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Publish this video — visible to students from this point on.
     */
    public function publish(): void
    {
        $this->update([
            'status' => LessonVideoStatus::Published,
            'published_at' => now(),
        ]);
    }
}

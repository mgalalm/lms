<?php

namespace App\Models;

use App\Enums\TeacherTaskType;
use Database\Factories\TeacherTaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A teacher's to-do item — system-generated (session_prep, session_review)
 * or admin-assigned (manual). session_prep tasks auto-complete when the
 * teacher opens the related lesson's Tutor Guide or Slides; the other two
 * types require an explicit "Mark complete" action.
 */
class TeacherTask extends Model
{
    /** @use HasFactory<TeacherTaskFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'teacher_id',
        'title',
        'description',
        'type',
        'class_session_id',
        'lesson_id',
        'created_by',
        'due_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TeacherTaskType::class,
            'due_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query->whereNull('completed_at')->where('due_at', '<', now());
    }

    public function isOverdue(): bool
    {
        return $this->completed_at === null && $this->due_at->isPast();
    }
}

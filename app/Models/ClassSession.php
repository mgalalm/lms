<?php

namespace App\Models;

use App\Enums\SessionStatus;
use App\Enums\SessionType;
use App\Observers\ClassSessionObserver;
use Database\Factories\ClassSessionFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[ObservedBy(ClassSessionObserver::class)]
class ClassSession extends Model
{
    /** @use HasFactory<ClassSessionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'teacher_id',
        'student_id',
        'lesson_id',
        'type',
        'status',
        'scheduled_at',
        'zoom_meeting_id',
        'zoom_join_url',
        'zoom_start_url',
        'zoom_password',
        'teacher_joined_at',
        'student_joined_at',
        'ended_at',
        'recap_path',
        'tags',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => SessionType::class,
            'status' => SessionStatus::class,
            'scheduled_at' => 'datetime',
            'teacher_joined_at' => 'datetime',
            'student_joined_at' => 'datetime',
            'ended_at' => 'datetime',
            'tags' => 'array',
        ];
    }

    /**
     * Get the teacher assigned to this session.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the student assigned to this session.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Get the lesson this session is delivering.
     */
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /**
     * Get this session's quality review, if one has been submitted.
     */
    public function review(): HasOne
    {
        return $this->hasOne(SessionReview::class);
    }

    /**
     * Get this session's support tickets.
     */
    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    /**
     * Get the most recently opened support ticket for this session, if any.
     */
    public function latestSupportTicket(): HasOne
    {
        return $this->hasOne(SupportTicket::class)->latestOfMany();
    }

    /**
     * How long after its scheduled start a session is still treated as
     * "current" by scopeCurrentOrUpcoming() if Zoom's meeting.ended
     * webhook hasn't marked it Completed yet — matches the default Zoom
     * meeting length set in CreateZoomMeetingJob, so a session stays
     * joinable for its whole expected duration rather than disappearing
     * the instant the clock passes its start time.
     */
    private const LIVE_WINDOW_MINUTES = 60;

    /**
     * Scope sessions that are scheduled to happen from now on.
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('scheduled_at', '>=', now())
            ->where('status', '!=', SessionStatus::Cancelled)
            ->orderBy('scheduled_at');
    }

    /**
     * Scope sessions worth surfacing as the dashboard's "next session" Join
     * card: still-genuinely-upcoming sessions, plus ones that started
     * recently and haven't been marked Completed (by Zoom's meeting.ended
     * webhook) or Cancelled yet — so a session a student/teacher is
     * currently in stays joinable for its whole duration instead of
     * vanishing the moment scheduled_at passes. Distinct from upcoming(),
     * which the schedule list's "Upcoming" filter tab intentionally keeps
     * meaning "hasn't started yet".
     */
    public function scopeCurrentOrUpcoming(Builder $query): Builder
    {
        return $query->where('scheduled_at', '>=', now()->subMinutes(self::LIVE_WINDOW_MINUTES))
            ->whereNotIn('status', [SessionStatus::Cancelled, SessionStatus::Completed])
            ->orderBy('scheduled_at');
    }

    /**
     * Scope sessions that have already happened (or been completed early).
     */
    public function scopePast(Builder $query): Builder
    {
        return $query->where(function (Builder $query) {
            $query->where('scheduled_at', '<', now())
                ->orWhere('status', SessionStatus::Completed);
        })->orderByDesc('scheduled_at');
    }

    /**
     * Scope sessions scheduled for today.
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('scheduled_at', today());
    }
}

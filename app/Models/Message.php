<?php

namespace App\Models;

use App\Enums\MessageModerationStatus;
use App\Enums\MessageType;
use App\Enums\UserRole;
use App\Events\MessageSent;
use App\Notifications\MessageFlaggedForReview;
use App\Observers\MessageObserver;
use Database\Factories\MessageFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Notification;

#[ObservedBy(MessageObserver::class)]
class Message extends Model
{
    /** @use HasFactory<MessageFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * moderation_status/moderation_flags/moderated_at are deliberately
     * excluded — they may only be changed via approveModeration() /
     * flagModeration(), never by mass assignment from a request.
     *
     * @var list<string>
     */
    protected $fillable = [
        'channel_id',
        'user_id',
        'type',
        'body',
        'meta',
        'attachments',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MessageType::class,
            'meta' => 'array',
            'attachments' => 'array',
            'moderation_status' => MessageModerationStatus::class,
            'moderation_flags' => 'array',
            'moderated_at' => 'datetime',
        ];
    }

    /**
     * Get the channel this message was posted in.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Get the user who sent this message.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope messages that have cleared moderation.
     */
    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('moderation_status', MessageModerationStatus::Approved);
    }

    /**
     * Scope messages a given viewer is allowed to see: anything approved,
     * plus their own messages regardless of moderation status (a sender
     * always sees what they wrote — moderation hides content from other
     * participants, not from the author).
     */
    public function scopeVisibleTo(Builder $query, User $viewer): Builder
    {
        return $query->where(function (Builder $query) use ($viewer) {
            $query->approved()->orWhere('user_id', $viewer->id);
        });
    }

    /**
     * Mark this message as having cleared moderation and reveal it to the
     * rest of the channel.
     */
    public function approveModeration(): void
    {
        $this->forceFill([
            'moderation_status' => MessageModerationStatus::Approved,
            'moderated_at' => now(),
        ])->save();

        $this->loadMissing(['user.teacherProfile', 'user.studentProfile']);

        broadcast(new MessageSent($this))->toOthers();
    }

    /**
     * Mark this message as flagged by moderation — it stays hidden from
     * every other participant — and alert admins for review.
     *
     * @param  list<string>  $categories
     */
    public function flagModeration(array $categories = []): void
    {
        $this->forceFill([
            'moderation_status' => MessageModerationStatus::Flagged,
            'moderation_flags' => $categories,
            'moderated_at' => now(),
        ])->save();

        Notification::send(
            User::role(UserRole::Admin->value)->get(),
            new MessageFlaggedForReview($this)
        );
    }
}

<?php

namespace App\Models;

use App\Enums\SupportTicketStatus;
use Database\Factories\SupportTicketFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupportTicket extends Model
{
    /** @use HasFactory<SupportTicketFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'teacher_id',
        'class_session_id',
        'channel_id',
        'subject',
        'description',
        'status',
        'resolved_by',
        'resolved_at',
        'rating',
        'comment',
        'rated_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SupportTicketStatus::class,
            'resolved_at' => 'datetime',
            'rating' => 'integer',
            'rated_at' => 'datetime',
        ];
    }

    /**
     * Generate a unique ticket code (e.g. "TN-58213") for new tickets.
     */
    protected static function booted(): void
    {
        static::creating(function (self $ticket) {
            if (! $ticket->code) {
                do {
                    $code = 'TN-'.random_int(10000, 99999);
                } while (self::where('code', $code)->exists());

                $ticket->code = $code;
            }
        });
    }

    /**
     * Get the teacher who opened this ticket.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the class session this ticket concerns, if any.
     */
    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    /**
     * Get the message thread channel for this ticket.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Get the admin who resolved this ticket, if any.
     */
    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}

<?php

namespace App\Models;

use Database\Factories\ChannelParticipantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChannelParticipant extends Model
{
    /** @use HasFactory<ChannelParticipantFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'channel_id',
        'user_id',
        'last_read_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_read_at' => 'datetime',
        ];
    }

    /**
     * Get the channel this participant belongs to.
     */
    public function channel(): BelongsTo
    {
        return $this->belongsTo(Channel::class);
    }

    /**
     * Get the user this participant record represents.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

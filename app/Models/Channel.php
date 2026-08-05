<?php

namespace App\Models;

use App\Enums\ChannelType;
use Database\Factories\ChannelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Channel extends Model
{
    /** @use HasFactory<ChannelFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'type',
        'name',
        'description',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ChannelType::class,
        ];
    }

    /**
     * Get the channel participant pivot rows.
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ChannelParticipant::class);
    }

    /**
     * Get the users who belong to this channel.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'channel_participants')
            ->withPivot('last_read_at')
            ->withTimestamps();
    }

    /**
     * Get the messages posted in this channel.
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * Get the most recent message in this channel.
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }
}

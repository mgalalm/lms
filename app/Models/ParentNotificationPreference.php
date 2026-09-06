<?php

namespace App\Models;

use App\Enums\ParentNotificationChannel;
use App\Enums\ParentNotificationType;
use Database\Factories\ParentNotificationPreferenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentNotificationPreference extends Model
{
    /** @use HasFactory<ParentNotificationPreferenceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'parent_contact_id',
        'notification_type',
        'channel',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'notification_type' => ParentNotificationType::class,
            'channel' => ParentNotificationChannel::class,
        ];
    }

    public function parentContact(): BelongsTo
    {
        return $this->belongsTo(ParentContact::class);
    }
}

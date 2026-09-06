<?php

namespace App\Models;

use App\Enums\ParentContactChannel;
use App\Enums\ParentPingDeliveryStatus;
use Database\Factories\ParentPingLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ParentPingLog extends Model
{
    /** @use HasFactory<ParentPingLogFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'class_session_id',
        'student_id',
        'triggered_by_user_id',
        'parent_contact_id',
        'channel',
        'destination',
        'status',
        'provider_message_id',
        'provider_status',
        'message',
        'error_detail',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channel' => ParentContactChannel::class,
            'destination' => 'encrypted',
            'status' => ParentPingDeliveryStatus::class,
        ];
    }

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }

    public function parentContact(): BelongsTo
    {
        return $this->belongsTo(ParentContact::class);
    }
}

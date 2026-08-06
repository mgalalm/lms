<?php

namespace App\Models;

use App\Enums\TeacherRequestStatus;
use App\Enums\TeacherRequestType;
use Database\Factories\TeacherRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class TeacherRequest extends Model
{
    /** @use HasFactory<TeacherRequestFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'teacher_profile_id',
        'type',
        'payload',
        'attachment_path',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TeacherRequestType::class,
            'payload' => 'array',
            'status' => TeacherRequestStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    /**
     * Get the teacher who submitted this request.
     */
    public function teacherProfile(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class);
    }

    /**
     * Get the admin who reviewed this request.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Number of days a Leave/Excused Time request covers, inclusive —
     * used to debit LeaveCredit when the request is approved.
     */
    public function dayCount(): int
    {
        $start = $this->payload['start_date'] ?? null;
        $end = $this->payload['end_date'] ?? $start;

        if (! $start) {
            return 0;
        }

        return Carbon::parse($start)->diffInDays(Carbon::parse($end)) + 1;
    }
}

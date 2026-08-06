<?php

namespace App\Models;

use Database\Factories\LeaveCreditFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveCredit extends Model
{
    /** @use HasFactory<LeaveCreditFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'teacher_profile_id',
        'monthly_limit',
        'monthly_used',
        'annual_limit',
        'annual_used',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'monthly_limit' => 'integer',
            'monthly_used' => 'integer',
            'annual_limit' => 'integer',
            'annual_used' => 'integer',
        ];
    }

    /**
     * Get the teacher this leave credit balance belongs to.
     */
    public function teacherProfile(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class);
    }
}

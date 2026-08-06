<?php

namespace App\Models;

use Database\Factories\InsightMetricFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InsightMetric extends Model
{
    /** @use HasFactory<InsightMetricFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'teacher_profile_id',
        'period_start',
        'period_end',
        'objections',
        'excuses',
        'study_level',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'objections' => 'integer',
            'excuses' => 'integer',
            'study_level' => 'integer',
        ];
    }

    /**
     * Get the teacher this rollup belongs to.
     */
    public function teacherProfile(): BelongsTo
    {
        return $this->belongsTo(TeacherProfile::class);
    }
}

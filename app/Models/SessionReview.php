<?php

namespace App\Models;

use App\Enums\FlagSeverity;
use Database\Factories\SessionReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionReview extends Model
{
    /** @use HasFactory<SessionReviewFactory> */
    use HasFactory;

    /**
     * The category score columns that make up the Performance Insights
     * chart and the teacher's overall Total Score.
     *
     * @var list<string>
     */
    public const CATEGORIES = [
        'feedback_score',
        'attitude_score',
        'preparation_score',
        'curriculum_score',
        'teaching_score',
        'setup_score',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'class_session_id',
        'reviewed_by',
        'star_rating',
        'sentiment',
        'flag_severity',
        'feedback_score',
        'attitude_score',
        'preparation_score',
        'curriculum_score',
        'teaching_score',
        'setup_score',
        'notes',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'flag_severity' => FlagSeverity::class,
            'star_rating' => 'integer',
            'feedback_score' => 'integer',
            'attitude_score' => 'integer',
            'preparation_score' => 'integer',
            'curriculum_score' => 'integer',
            'teaching_score' => 'integer',
            'setup_score' => 'integer',
        ];
    }

    /**
     * Get the class session this review is for.
     */
    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    /**
     * Get the admin/reviewer who submitted this review.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * The average of the six category scores — this review's contribution
     * to the teacher's overall Total Score.
     */
    public function averageScore(): int
    {
        $scores = array_map(fn (string $category) => $this->{$category}, self::CATEGORIES);

        return (int) round(array_sum($scores) / count($scores));
    }
}

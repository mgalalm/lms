<?php

namespace App\Models;

use App\Enums\LessonQuizQuestionType;
use Database\Factories\LessonQuizQuestionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonQuizQuestion extends Model
{
    /** @use HasFactory<LessonQuizQuestionFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lesson_quiz_id',
        'question_text',
        'type',
        'options',
        'correct_answer',
        'explanation',
        'is_ai_generated',
        'sort_order',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => LessonQuizQuestionType::class,
            'options' => 'array',
            'is_ai_generated' => 'boolean',
        ];
    }

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(LessonQuiz::class, 'lesson_quiz_id');
    }
}

<?php

namespace Database\Factories;

use App\Enums\LessonQuizQuestionType;
use App\Models\LessonQuiz;
use App\Models\LessonQuizQuestion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonQuizQuestion>
 */
class LessonQuizQuestionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_quiz_id' => LessonQuiz::factory(),
            'question_text' => fake()->sentence().'?',
            'type' => LessonQuizQuestionType::MultipleChoice,
            'options' => ['Option A', 'Option B', 'Option C', 'Option D'],
            'correct_answer' => 'Option A',
            'explanation' => fake()->sentence(),
            'is_ai_generated' => true,
            'sort_order' => 0,
        ];
    }
}

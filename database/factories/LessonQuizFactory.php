<?php

namespace Database\Factories;

use App\Enums\LessonQuizStatus;
use App\Models\Lesson;
use App\Models\LessonQuiz;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonQuiz>
 */
class LessonQuizFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_id' => Lesson::factory(),
            'locale' => 'en',
            'generated_at' => now(),
            'source_text_hash' => hash('sha256', fake()->paragraphs(3, true)),
            'status' => LessonQuizStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LessonQuizStatus::Published,
        ]);
    }
}

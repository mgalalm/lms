<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonTutorGuide;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonTutorGuide>
 */
class LessonTutorGuideFactory extends Factory
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
            'procedure_steps' => [
                ['phase' => 'explore', 'title' => fake()->sentence(4), 'duration_minutes' => 10],
                ['phase' => 'evaluate', 'title' => fake()->sentence(4), 'duration_minutes' => 15],
            ],
            'glossary_terms' => [
                ['term' => fake()->word(), 'definition' => fake()->sentence()],
            ],
        ];
    }
}

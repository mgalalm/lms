<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonProject;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonProject>
 */
class LessonProjectFactory extends Factory
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
            'title' => fake()->sentence(4),
            'brief' => fake()->paragraph(),
            'is_required' => false,
        ];
    }
}

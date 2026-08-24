<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\LessonSlideshow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LessonSlideshow>
 */
class LessonSlideshowFactory extends Factory
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
            'file_path' => 'lesson-slideshows/'.fake()->uuid().'.pdf',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['file_path' => null]);
    }
}

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
        $id = fake()->regexify('[a-zA-Z0-9_-]{44}');

        return [
            'lesson_id' => Lesson::factory(),
            'locale' => 'en',
            'google_slides_url' => "https://docs.google.com/presentation/d/{$id}/edit",
            'google_slides_id' => $id,
        ];
    }

    public function empty(): static
    {
        return $this->state(fn (array $attributes) => [
            'google_slides_url' => null,
            'google_slides_id' => null,
        ]);
    }
}

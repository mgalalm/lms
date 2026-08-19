<?php

namespace Database\Factories;

use App\Enums\LessonVideoStatus;
use App\Models\Lesson;
use App\Models\LessonVideo;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<LessonVideo>
 */
class LessonVideoFactory extends Factory
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
            'bunny_video_id' => (string) Str::uuid(),
            'status' => LessonVideoStatus::Uploading,
            'duration_seconds' => null,
            'thumbnail_url' => null,
            'uploaded_by' => User::factory(),
            'published_at' => null,
        ];
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => ['status' => LessonVideoStatus::Processing]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => LessonVideoStatus::Draft,
            'duration_seconds' => fake()->numberBetween(120, 900),
            'thumbnail_url' => 'https://vz-example.b-cdn.net/'.fake()->uuid().'/thumbnail.jpg',
        ]);
    }

    public function published(): static
    {
        return $this->draft()->state(fn (array $attributes) => [
            'status' => LessonVideoStatus::Published,
            'published_at' => now(),
        ]);
    }
}

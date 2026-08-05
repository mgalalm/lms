<?php

namespace Database\Factories;

use App\Enums\SessionStatus;
use App\Enums\SessionType;
use App\Models\ClassSession;
use App\Models\Lesson;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ClassSession>
 */
class ClassSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_id' => User::factory()->teacher(),
            'student_id' => User::factory()->student(),
            'lesson_id' => Lesson::factory(),
            'type' => SessionType::Online,
            'status' => SessionStatus::Scheduled,
            'scheduled_at' => fake()->dateTimeBetween('now', '+1 week'),
        ];
    }

    /**
     * Indicate that the session has already been completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::Completed,
            'scheduled_at' => fake()->dateTimeBetween('-1 week', '-1 hour'),
            'ended_at' => fake()->dateTimeBetween('-1 week', 'now'),
        ]);
    }
}

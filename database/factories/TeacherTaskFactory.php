<?php

namespace Database\Factories;

use App\Enums\TeacherTaskType;
use App\Models\TeacherTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherTask>
 */
class TeacherTaskFactory extends Factory
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
            'title' => fake()->sentence(4),
            'description' => fake()->sentence(12),
            'type' => TeacherTaskType::Manual,
            'due_at' => fake()->dateTimeBetween('now', '+1 week'),
        ];
    }

    public function overdue(): static
    {
        return $this->state(fn (array $attributes) => ['due_at' => fake()->dateTimeBetween('-1 week', '-1 hour')]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => ['completed_at' => now()]);
    }
}

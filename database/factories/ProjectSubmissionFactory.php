<?php

namespace Database\Factories;

use App\Enums\ProjectSubmissionStatus;
use App\Models\LessonProject;
use App\Models\ProjectSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectSubmission>
 */
class ProjectSubmissionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_project_id' => LessonProject::factory(),
            'student_id' => User::factory()->student(),
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'live_url' => fake()->url(),
            'status' => ProjectSubmissionStatus::Submitted,
            'submitted_at' => now(),
        ];
    }

    /**
     * Indicate that the submission has already been reviewed.
     */
    public function reviewed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ProjectSubmissionStatus::Reviewed,
        ]);
    }
}

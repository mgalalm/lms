<?php

namespace Database\Factories;

use App\Models\ProjectFeedback;
use App\Models\ProjectSubmission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProjectFeedback>
 */
class ProjectFeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_submission_id' => ProjectSubmission::factory(),
            'teacher_id' => User::factory()->teacher(),
            'rating' => fake()->numberBetween(1, 5),
            'comment' => fake()->paragraph(),
            'is_featured' => false,
            'reviewed_at' => now(),
        ];
    }
}

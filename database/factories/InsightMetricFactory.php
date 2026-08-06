<?php

namespace Database\Factories;

use App\Models\InsightMetric;
use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InsightMetric>
 */
class InsightMetricFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_profile_id' => TeacherProfile::factory(),
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'objections' => fake()->numberBetween(0, 3),
            'excuses' => fake()->numberBetween(0, 3),
            'study_level' => fake()->numberBetween(0, 10),
        ];
    }
}

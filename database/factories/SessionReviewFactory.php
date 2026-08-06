<?php

namespace Database\Factories;

use App\Models\ClassSession;
use App\Models\SessionReview;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionReview>
 */
class SessionReviewFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'class_session_id' => ClassSession::factory(),
            'star_rating' => fake()->numberBetween(3, 5),
            'sentiment' => fake()->randomElement(['positive', 'neutral', 'negative']),
            'flag_severity' => 'green',
            'feedback_score' => fake()->numberBetween(70, 100),
            'attitude_score' => fake()->numberBetween(70, 100),
            'preparation_score' => fake()->numberBetween(70, 100),
            'curriculum_score' => fake()->numberBetween(70, 100),
            'teaching_score' => fake()->numberBetween(70, 100),
            'setup_score' => fake()->numberBetween(70, 100),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentProfile>
 */
class StudentProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'code' => 'S-'.fake()->unique()->numberBetween(100000, 999999),
            'grade_level' => fake()->randomElement(['Grade 1-2', 'Grade 3-4', 'Grade 5-6', 'Grade 7-8']),
            'language' => fake()->randomElement(['English', 'Arabic']),
            'points_balance' => fake()->numberBetween(0, 5000),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => 'M'.fake()->numberBetween(1, 12).': '.fake()->words(3, true),
            'grade_band' => fake()->randomElement(['Grade 1-2', 'Grade 3-4', 'Grade 5-6', 'Grade 7-8']),
        ];
    }
}

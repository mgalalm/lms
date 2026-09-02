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
            // Not ->unique() — only 4 possible values, would throw
            // OverflowException once exhausted across the whole suite run.
            'grade_band' => fake()->randomElement(['Grade 5 - 6', 'Grade 7 - 8', 'Grade 9 - 10', 'Grade 11 - 12']),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\PayrollEntry;
use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollEntry>
 */
class PayrollEntryFactory extends Factory
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
            'month' => now()->startOfMonth(),
            'organization' => 'GenAcademy',
            'sessions_count' => fake()->numberBetween(10, 60),
            'basic_salary' => fake()->numberBetween(3000, 8000),
            'status' => 'pending',
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'completed_at' => now(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\TeacherProfile;
use App\Models\TeacherRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherRequest>
 */
class TeacherRequestFactory extends Factory
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
            'type' => 'leave',
            'payload' => [
                'leave_type' => 'planned',
                'sub_reason' => 'Vacation',
                'start_date' => now()->addWeek()->toDateString(),
                'end_date' => now()->addWeek()->toDateString(),
                'notes' => fake()->sentence(),
            ],
            'status' => 'pending',
        ];
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_at' => now(),
        ]);
    }
}

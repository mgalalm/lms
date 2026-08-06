<?php

namespace Database\Factories;

use App\Models\LeaveCredit;
use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveCredit>
 */
class LeaveCreditFactory extends Factory
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
            'monthly_limit' => 5,
            'monthly_used' => 0,
            'annual_limit' => 15,
            'annual_used' => 0,
        ];
    }
}

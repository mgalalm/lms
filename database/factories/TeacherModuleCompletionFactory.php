<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\TeacherModuleCompletion;
use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherModuleCompletion>
 */
class TeacherModuleCompletionFactory extends Factory
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
            'module_id' => Module::factory(),
            'completed_at' => now(),
            'quiz_passed' => true,
        ];
    }
}

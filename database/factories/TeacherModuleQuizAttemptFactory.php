<?php

namespace Database\Factories;

use App\Models\Module;
use App\Models\TeacherModuleQuizAttempt;
use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherModuleQuizAttempt>
 */
class TeacherModuleQuizAttemptFactory extends Factory
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
            'question_ids' => [],
            'bank_fingerprint' => null,
            'score' => 0,
            'passed' => false,
            'submitted_at' => now(),
        ];
    }

    public function passed(int $score = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $score,
            'passed' => true,
        ]);
    }

    /**
     * A drawn-but-not-yet-submitted attempt.
     */
    public function open(): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => null,
            'passed' => null,
            'submitted_at' => null,
        ]);
    }

    public function voided(): static
    {
        return $this->state(fn (array $attributes) => [
            'voided_at' => now(),
        ]);
    }
}

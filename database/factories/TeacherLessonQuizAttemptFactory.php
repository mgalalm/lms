<?php

namespace Database\Factories;

use App\Models\LessonQuiz;
use App\Models\TeacherLessonQuizAttempt;
use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherLessonQuizAttempt>
 */
class TeacherLessonQuizAttemptFactory extends Factory
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
            'lesson_quiz_id' => LessonQuiz::factory(),
            'score' => 0,
            'passed' => false,
        ];
    }

    public function passed(int $score = 100): static
    {
        return $this->state(fn (array $attributes) => [
            'score' => $score,
            'passed' => true,
        ]);
    }

    public function voided(): static
    {
        return $this->state(fn (array $attributes) => [
            'voided_at' => now(),
        ]);
    }
}

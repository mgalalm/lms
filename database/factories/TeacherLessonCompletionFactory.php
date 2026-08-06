<?php

namespace Database\Factories;

use App\Models\Lesson;
use App\Models\TeacherLessonCompletion;
use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherLessonCompletion>
 */
class TeacherLessonCompletionFactory extends Factory
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
            'lesson_id' => Lesson::factory(),
            'completed_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}

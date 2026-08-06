<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\TeacherCourseCompletion;
use App\Models\TeacherProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherCourseCompletion>
 */
class TeacherCourseCompletionFactory extends Factory
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
            'course_id' => Course::factory(),
            'completed_at' => null,
            'quiz_passed' => false,
        ];
    }

    /**
     * Indicate that the teacher has fully certified for this course.
     */
    public function certified(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => fake()->dateTimeBetween('-1 year', 'now'),
            'quiz_passed' => true,
        ]);
    }
}

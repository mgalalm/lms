<?php

namespace Database\Factories;

use App\Models\TeacherProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherProfile>
 */
class TeacherProfileFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'code' => 'T-'.fake()->unique()->numberBetween(10000, 99999),
            'rank' => 'R '.fake()->numberBetween(1, 5),
            'starting_date' => fake()->dateTimeBetween('-5 years', 'now'),
            'languages' => fake()->randomElements(['English', 'Arabic', 'French'], 2),
            'faculty_of_graduation' => fake()->randomElement(['Faculty of Engineering', 'Faculty of Computers and Information', 'Faculty of Education']),
            'graduation_year' => fake()->numberBetween(2000, 2025),
            'employment_type' => fake()->randomElement(['Full time', 'Part time']),
            'tutor_team_id' => fake()->numerify('TEAM-###'),
            'team_leader_name' => fake()->name(),
            'mentor_name' => fake()->name(),
            'bank_name' => fake()->randomElement(['CIB', 'NBE', 'QNB', 'Banque Misr']),
            'bank_account_number' => fake()->numerify('##########'),
            'zoom_user_id' => fake()->safeEmail(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Module>
 */
class ModuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Reuse an already-existing course rather than always minting a
            // new random one — with only 4 fixed grade bands and a unique
            // constraint, several bare Module::factory() calls in the same
            // test (e.g. via ClassSession::factory()->count(3)) would
            // otherwise have a real chance of colliding on grade_band.
            'course_id' => fn () => Course::query()->inRandomOrder()->value('id') ?? Course::factory(),
            'title' => fake()->words(4, true),
            'order' => fake()->numberBetween(0, 10),
        ];
    }
}

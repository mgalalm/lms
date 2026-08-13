<?php

namespace Database\Factories;

use App\Models\SessionPackage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionPackage>
 */
class SessionPackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sessionCount = fake()->randomElement([5, 10, 20]);

        return [
            'name' => "{$sessionCount}-Session Package",
            'description' => fake()->sentence(),
            'session_count' => $sessionCount,
            'price_cents' => $sessionCount * 3000,
            'currency' => 'eur',
            'stripe_price_id' => 'price_'.fake()->unique()->bothify('??????????????'),
            'active' => true,
        ];
    }
}

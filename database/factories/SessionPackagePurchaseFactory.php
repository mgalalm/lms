<?php

namespace Database\Factories;

use App\Enums\SessionPackagePurchaseStatus;
use App\Models\SessionPackage;
use App\Models\SessionPackagePurchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SessionPackagePurchase>
 */
class SessionPackagePurchaseFactory extends Factory
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
            'session_package_id' => SessionPackage::factory(),
            'status' => SessionPackagePurchaseStatus::Pending,
            'session_count' => fake()->randomElement([5, 10, 20]),
            'price_cents' => fake()->numberBetween(1500, 60000),
            'currency' => 'eur',
            'stripe_checkout_session_id' => 'cs_test_'.fake()->unique()->bothify('??????????????'),
        ];
    }

    /**
     * Indicate that the purchase has been paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionPackagePurchaseStatus::Paid,
            'paid_at' => now(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Models\ReferralCode;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<ReferralCode>
 */
class ReferralCodeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => Str::upper(Str::random(8)),
            'referrer_id' => User::factory(),
            'reward_sessions_referrer' => 1,
            'reward_sessions_referee' => 1,
            'uses_remaining' => null,
            'expires_at' => null,
        ];
    }

    /**
     * Indicate that the code has already been fully redeemed.
     */
    public function exhausted(): static
    {
        return $this->state(fn (array $attributes) => ['uses_remaining' => 0]);
    }

    /**
     * Indicate that the code has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => ['expires_at' => now()->subDay()]);
    }
}

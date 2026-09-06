<?php

namespace Database\Factories;

use App\Enums\ParentContactChannel;
use App\Models\ParentContact;
use App\Models\ParentContactVerification;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<ParentContactVerification>
 */
class ParentContactVerificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_contact_id' => ParentContact::factory(),
            'channel' => ParentContactChannel::Email,
            'code_hash' => Hash::make('123456'),
            'destination' => fake()->safeEmail(),
            'expires_at' => now()->addMinutes(15),
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subMinute(),
        ]);
    }
}

<?php

namespace Database\Factories;

use App\Enums\ChannelType;
use App\Models\Channel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Channel>
 */
class ChannelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => ChannelType::Direct,
            'name' => null,
        ];
    }

    /**
     * Indicate that this is a named group channel.
     */
    public function group(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => ChannelType::Group,
            'name' => fake()->randomElement(['Announcements', 'Grade 5-6 - M1: Intro to Python', 'Grade 5-6 - M2: Chatbot Development']),
        ]);
    }
}

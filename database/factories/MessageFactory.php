<?php

namespace Database\Factories;

use App\Enums\MessageType;
use App\Models\Channel;
use App\Models\Message;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'channel_id' => Channel::factory(),
            'user_id' => User::factory(),
            'type' => MessageType::Text,
            'body' => fake()->paragraph(),
        ];
    }

    /**
     * Indicate that this message is a task/reminder card.
     */
    public function taskCard(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => MessageType::TaskCard,
            'body' => null,
            'meta' => [
                'title' => 'Task',
                'accent_color' => 'blue',
                'icon' => 'clipboard-list',
            ],
        ]);
    }
}

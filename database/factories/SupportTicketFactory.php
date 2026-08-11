<?php

namespace Database\Factories;

use App\Enums\ChannelType;
use App\Enums\SupportTicketStatus;
use App\Models\Channel;
use App\Models\SupportTicket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportTicket>
 */
class SupportTicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'teacher_id' => User::factory()->teacher(),
            'channel_id' => Channel::factory()->state(['type' => ChannelType::Support]),
            'subject' => fake()->randomElement(['Frozen Video', 'Student No Show', 'Zoom Link Not Working']),
            'description' => fake()->sentence(12),
            'status' => SupportTicketStatus::Open,
        ];
    }

    /**
     * Indicate that this ticket has been resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SupportTicketStatus::Resolved,
            'resolved_by' => User::factory(),
            'resolved_at' => now(),
        ]);
    }
}

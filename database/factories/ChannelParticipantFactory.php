<?php

namespace Database\Factories;

use App\Models\Channel;
use App\Models\ChannelParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ChannelParticipant>
 */
class ChannelParticipantFactory extends Factory
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
            'last_read_at' => null,
        ];
    }
}

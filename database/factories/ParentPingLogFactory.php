<?php

namespace Database\Factories;

use App\Enums\ParentPingDeliveryStatus;
use App\Models\ParentPingLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParentPingLog>
 */
class ParentPingLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => User::factory()->student(),
            'triggered_by_user_id' => User::factory()->teacher(),
            'status' => ParentPingDeliveryStatus::Sent,
            'message' => fake()->sentence(),
        ];
    }
}

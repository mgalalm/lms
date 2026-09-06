<?php

namespace Database\Factories;

use App\Enums\ParentNotificationChannel;
use App\Enums\ParentNotificationType;
use App\Models\ParentContact;
use App\Models\ParentNotificationPreference;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ParentNotificationPreference>
 */
class ParentNotificationPreferenceFactory extends Factory
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
            'notification_type' => ParentNotificationType::UrgentPing,
            'channel' => ParentNotificationChannel::Sms,
        ];
    }
}

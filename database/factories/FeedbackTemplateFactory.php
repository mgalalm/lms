<?php

namespace Database\Factories;

use App\Enums\FeedbackSentiment;
use App\Models\FeedbackTemplate;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeedbackTemplate>
 */
class FeedbackTemplateFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => fake()->words(3, true),
            'body' => fake()->paragraph(),
            'sentiment' => fake()->randomElement(FeedbackSentiment::cases()),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}

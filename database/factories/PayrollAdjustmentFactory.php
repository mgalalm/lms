<?php

namespace Database\Factories;

use App\Models\PayrollAdjustment;
use App\Models\PayrollEntry;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PayrollAdjustment>
 */
class PayrollAdjustmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payroll_entry_id' => PayrollEntry::factory(),
            'type' => fake()->randomElement(['bonus', 'deduction']),
            'amount' => fake()->numberBetween(50, 500),
            'reason' => fake()->sentence(4),
        ];
    }
}

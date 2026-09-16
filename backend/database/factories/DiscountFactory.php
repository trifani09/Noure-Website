<?php

namespace Database\Factories;

use App\Models\Discount;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Discount>
 */
class DiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('SAVE##??')),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'type' => 'percentage',
            'value' => 1500,
            'currency' => null,
            'minimum_order_amount' => null,
            'maximum_discount_amount' => null,
            'usage_limit' => null,
            'usage_limit_per_customer' => 1,
            'used_count' => 0,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'is_active' => true,
        ];
    }
}

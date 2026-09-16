<?php

namespace Database\Factories;

use App\Models\Cart;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => null,
            'guest_token_hash' => hash('sha256', fake()->unique()->uuid()),
            'status' => 'active',
            'currency' => 'IDR',
            'email' => fake()->safeEmail(),
            'expires_at' => now()->addDays(7),
            'converted_at' => null,
        ];
    }
}

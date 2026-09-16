<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_number' => 'NOU-'.fake()->unique()->numerify('########'),
            'customer_id' => null,
            'status' => 'pending',
            'payment_status' => 'pending',
            'fulfillment_status' => 'unfulfilled',
            'placed_at' => now(),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'currency' => 'IDR',
            'subtotal_amount' => 250000,
            'discount_amount' => 0,
            'shipping_amount' => 20000,
            'tax_amount' => 0,
            'grand_total_amount' => 270000,
            'billing_address' => $this->addressSnapshot(),
            'shipping_address' => $this->addressSnapshot(),
            'metadata' => [],
        ];
    }

    /** @return array<string, string> */
    private function addressSnapshot(): array
    {
        return [
            'recipient_name' => fake()->name(),
            'line1' => fake()->streetAddress(),
            'city' => fake()->city(),
            'postal_code' => fake()->postcode(),
            'country_code' => 'ID',
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'provider' => 'test',
            'provider_payment_id' => fake()->unique()->uuid(),
            'method_type' => 'bank_transfer',
            'status' => 'pending',
            'amount' => 270000,
            'currency' => 'IDR',
            'idempotency_key' => fake()->unique()->uuid(),
            'metadata' => [],
        ];
    }
}

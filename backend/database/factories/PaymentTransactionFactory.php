<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PaymentTransaction>
 */
class PaymentTransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'payment_id' => Payment::factory(),
            'type' => 'authorization',
            'status' => 'succeeded',
            'amount' => 270000,
            'currency' => 'IDR',
            'provider_transaction_id' => fake()->unique()->uuid(),
            'idempotency_key' => fake()->unique()->uuid(),
            'response_metadata' => [],
            'processed_at' => now(),
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
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
            'product_id' => null,
            'variant_id' => null,
            'product_name' => fake()->words(3, true),
            'variant_name' => 'Cream / M',
            'sku' => fake()->unique()->bothify('NOU-####-????'),
            'option_values' => ['Color' => 'Cream', 'Size' => 'M'],
            'quantity' => 1,
            'unit_price_amount' => 250000,
            'subtotal_amount' => 250000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'total_amount' => 250000,
            'currency' => 'IDR',
        ];
    }
}

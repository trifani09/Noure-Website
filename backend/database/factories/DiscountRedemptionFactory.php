<?php

namespace Database\Factories;

use App\Models\Discount;
use App\Models\DiscountRedemption;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DiscountRedemption>
 */
class DiscountRedemptionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'discount_id' => Discount::factory(),
            'order_id' => Order::factory(),
            'customer_id' => null,
            'code_snapshot' => 'SAVE15',
            'amount' => 37500,
            'currency' => 'IDR',
        ];
    }
}

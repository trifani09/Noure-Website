<?php

namespace Database\Factories;

use App\Models\ProductOption;
use App\Models\ProductOptionValue;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductOptionValue>
 */
class ProductOptionValueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_option_id' => ProductOption::factory(),
            'label' => fake()->safeColorName(),
            'code' => fake()->unique()->slug(1),
            'swatch_value' => fake()->hexColor(),
            'sort_order' => 0,
        ];
    }
}

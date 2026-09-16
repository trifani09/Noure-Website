<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'sku' => fake()->unique()->bothify('NOU-####-????'),
            'title' => fake()->colorName().' / M',
            'combination_key' => fake()->unique()->numerify('###:###'),
            'price_amount' => fake()->numberBetween(100000, 2000000),
            'compare_at_amount' => null,
            'currency' => 'IDR',
            'barcode' => fake()->unique()->ean13(),
            'weight_grams' => fake()->numberBetween(100, 2000),
            'is_default' => false,
            'is_active' => true,
        ];
    }
}

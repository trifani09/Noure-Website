<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(3, true),
            'slug' => fake()->unique()->slug(),
            'short_description' => fake()->sentence(),
            'description' => fake()->paragraphs(3, true),
            'status' => 'active',
            'brand' => 'Noure',
            'published_at' => now(),
            'metadata' => [],
        ];
    }
}

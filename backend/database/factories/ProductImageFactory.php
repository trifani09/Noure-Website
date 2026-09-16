<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductImage>
 */
class ProductImageFactory extends Factory
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
            'path' => 'products/'.fake()->uuid().'.webp',
            'alt_text' => fake()->sentence(4),
            'width' => 1200,
            'height' => 1600,
            'mime_type' => 'image/webp',
            'sort_order' => 0,
            'is_primary' => true,
        ];
    }
}

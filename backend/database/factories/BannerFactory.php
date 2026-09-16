<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'placement' => 'home_hero',
            'headline' => fake()->sentence(5),
            'subheading' => fake()->sentence(),
            'cta_label' => 'Shop now',
            'cta_url' => '/collections/new-arrivals',
            'desktop_image_path' => 'banners/'.fake()->uuid().'-desktop.webp',
            'mobile_image_path' => 'banners/'.fake()->uuid().'-mobile.webp',
            'alt_text' => fake()->sentence(4),
            'sort_order' => 0,
            'is_active' => true,
            'starts_at' => now()->subDay(),
            'ends_at' => now()->addMonth(),
            'metadata' => [],
        ];
    }
}

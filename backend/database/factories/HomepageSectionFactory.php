<?php

namespace Database\Factories;

use App\Models\HomepageSection;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<HomepageSection> */
class HomepageSectionFactory extends Factory
{
    public function definition(): array
    {
        return ['name' => fake()->words(3, true), 'type' => 'brand_story', 'is_active' => true, 'sort_order' => 0, 'configuration' => ['heading' => fake()->sentence(3), 'body' => fake()->paragraph()]];
    }
}

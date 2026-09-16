<?php

namespace Database\Factories;

use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryLevel>
 */
class InventoryLevelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'variant_id' => ProductVariant::factory(),
            'location_id' => InventoryLocation::factory(),
            'on_hand' => 20,
            'reserved' => 2,
            'safety_stock' => 1,
            'version' => 0,
        ];
    }
}

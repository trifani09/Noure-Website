<?php

namespace Database\Factories;

use App\Models\InventoryLevel;
use App\Models\InventoryMovement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryMovement>
 */
class InventoryMovementFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'inventory_level_id' => InventoryLevel::factory(),
            'quantity_delta' => 10,
            'movement_type' => 'receipt',
            'reference_type' => null,
            'reference_id' => null,
            'reason' => 'Initial stock receipt',
        ];
    }
}

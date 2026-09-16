<?php

namespace Database\Factories;

use App\Models\InventoryLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryLocation>
 */
class InventoryLocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->unique()->bothify('LOC-###'),
            'name' => fake()->city().' Warehouse',
            'address' => ['city' => fake()->city(), 'country_code' => 'ID'],
            'contact' => ['phone' => fake()->phoneNumber()],
            'is_active' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Address>
 */
class AddressFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'label' => 'Home',
            'recipient_name' => fake()->name(),
            'phone' => fake()->phoneNumber(),
            'line1' => fake()->streetAddress(),
            'line2' => null,
            'city' => fake()->city(),
            'province' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'country_code' => 'ID',
            'is_default_shipping' => true,
            'is_default_billing' => true,
        ];
    }
}

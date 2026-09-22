<?php

namespace App\Customer;

use App\Models\Address;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerAddressService
{
    public function create(Customer $customer, array $attributes): Address
    {
        return DB::transaction(function () use ($customer, $attributes): Address {
            $hasAddresses = $customer->addresses()->exists();
            if (! $hasAddresses) {
                $attributes['is_default_shipping'] = $attributes['is_default_shipping'] ?? true;
                $attributes['is_default_billing'] = $attributes['is_default_billing'] ?? true;
            }
            $address = $customer->addresses()->create($attributes);
            $this->applyDefaults($customer, $address, $attributes);

            return $address->fresh();
        });
    }

    public function update(Address $address, array $attributes): Address
    {
        return DB::transaction(function () use ($address, $attributes): Address {
            $previousDefaults = [
                'is_default_shipping' => $address->is_default_shipping,
                'is_default_billing' => $address->is_default_billing,
            ];
            $address->update($attributes);
            $this->applyDefaults($address->customer, $address, $attributes);
            foreach ($previousDefaults as $field => $wasDefault) {
                if ($wasDefault && array_key_exists($field, $attributes) && ! $attributes[$field]) {
                    $replacement = $address->customer->addresses()->where('id', '!=', $address->getKey())->latest('id')->first();
                    if ($replacement) $this->setDefault($address->customer, $replacement, $field);
                }
            }

            return $address->fresh();
        });
    }

    public function delete(Address $address): void
    {
        DB::transaction(function () use ($address): void {
            $customer = $address->customer;
            $wasShipping = $address->is_default_shipping;
            $wasBilling = $address->is_default_billing;
            $address->delete();
            $replacement = $customer->addresses()->latest('id')->first();
            if ($replacement && $wasShipping) {
                $this->setDefault($customer, $replacement, 'is_default_shipping');
            }
            if ($replacement && $wasBilling) {
                $this->setDefault($customer, $replacement, 'is_default_billing');
            }
        });
    }

    private function applyDefaults(Customer $customer, Address $address, array $attributes): void
    {
        foreach (['is_default_shipping', 'is_default_billing'] as $field) {
            if (($attributes[$field] ?? false) === true) {
                $this->setDefault($customer, $address, $field);
            }
        }
    }

    private function setDefault(Customer $customer, Address $address, string $field): void
    {
        $customer->addresses()->where('id', '!=', $address->getKey())->update([$field => false]);
        $address->update([$field => true]);
    }
}
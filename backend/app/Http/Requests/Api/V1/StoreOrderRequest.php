<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Validation\Rule;

class StoreOrderRequest extends CustomerRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        $guest = ! auth('customer')->check();

        return [
            'name' => [Rule::requiredIf($guest), 'string', 'max:200'],
            'email' => [Rule::requiredIf($guest), 'email', 'max:255'],
            'phone' => [Rule::requiredIf($guest), 'string', 'max:50'],
            'address_public_id' => ['nullable', 'string', 'size:26', 'prohibits:shipping_address'],
            'shipping_address' => [Rule::requiredIf($guest), 'array:line1,line2,city,province,postal_code,country_code'],
            'shipping_address.line1' => ['required_with:shipping_address', 'string', 'max:255'],
            'shipping_address.line2' => ['nullable', 'string', 'max:255'],
            'shipping_address.city' => ['required_with:shipping_address', 'string', 'max:120'],
            'shipping_address.province' => ['nullable', 'string', 'max:120'],
            'shipping_address.postal_code' => ['required_with:shipping_address', 'string', 'max:30'],
            'shipping_address.country_code' => ['nullable', 'string', 'size:2'],
        ];
    }
}

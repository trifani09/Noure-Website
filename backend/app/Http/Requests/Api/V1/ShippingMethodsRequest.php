<?php

namespace App\Http\Requests\Api\V1;

class ShippingMethodsRequest extends CustomerRequest
{
    public function rules(): array
    {
        return [
            'address_public_id' => ['nullable', 'string', 'size:26'],
            'country_code' => ['required_without:address_public_id', 'nullable', 'string', 'size:2'],
            'city' => ['required_without:address_public_id', 'nullable', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['nullable', 'string', 'max:30'],
        ];
    }
}
<?php

namespace App\Http\Requests\Api\V1;

class StoreCustomerAddressRequest extends CustomerRequest
{
    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:100'],
            'recipient_name' => ['required', 'string', 'max:200'],
            'phone' => ['required', 'string', 'max:50', 'regex:/^\+?[0-9][0-9\s().-]{6,24}$/'],
            'line1' => ['required', 'string', 'max:255'],
            'line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:120'],
            'province' => ['nullable', 'string', 'max:120'],
            'postal_code' => ['required', 'string', 'max:30'],
            'country_code' => ['required', 'string', 'size:2', 'alpha'],
            'is_default_shipping' => ['sometimes', 'boolean'],
            'is_default_billing' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['label', 'recipient_name', 'phone', 'line1', 'line2', 'city', 'province', 'postal_code', 'country_code'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
        if (is_string($this->input('country_code'))) {
            $this->merge(['country_code' => strtoupper($this->input('country_code'))]);
        }
    }
}
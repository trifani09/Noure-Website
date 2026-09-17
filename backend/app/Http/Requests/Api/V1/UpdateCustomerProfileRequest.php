<?php

namespace App\Http\Requests\Api\V1;

class UpdateCustomerProfileRequest extends CustomerRequest
{
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:50', 'regex:/^\+?[0-9][0-9\s().-]{6,24}$/'],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['first_name', 'last_name', 'phone'] as $field) {
            if (is_string($this->input($field))) {
                $this->merge([$field => trim($this->input($field))]);
            }
        }
    }
}

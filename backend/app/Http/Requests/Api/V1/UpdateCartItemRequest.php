<?php

namespace App\Http\Requests\Api\V1;

class UpdateCartItemRequest extends CustomerRequest
{
    public function rules(): array
    {
        return ['quantity' => ['required', 'integer', 'min:1', 'max:999']];
    }
}

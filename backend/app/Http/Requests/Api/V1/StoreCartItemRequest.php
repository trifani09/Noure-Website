<?php

namespace App\Http\Requests\Api\V1;

class StoreCartItemRequest extends CustomerRequest
{
    public function rules(): array
    {
        return ['variant_public_id' => ['required', 'string', 'max:26'], 'quantity' => ['required', 'integer', 'min:1', 'max:999']];
    }
}

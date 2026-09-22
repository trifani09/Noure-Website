<?php

namespace App\Http\Requests\Api\V1;

class ListCustomerOrdersRequest extends CustomerRequest
{
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'status' => ['sometimes', 'string', 'max:30'],
            'payment_status' => ['sometimes', 'string', 'max:30'],
            'sort' => ['sometimes', 'in:newest,oldest'],
        ];
    }
}
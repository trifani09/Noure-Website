<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;
use Illuminate\Validation\Rule;

class ListOrdersRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'min:1', 'max:100'],
            'status' => ['sometimes', Rule::in(['pending', 'processing', 'shipped', 'completed', 'cancelled'])],
            'payment_status' => ['sometimes', Rule::in(['unpaid', 'pending', 'paid', 'failed', 'refunded'])],
            'date_from' => ['sometimes', 'date_format:Y-m-d', 'before_or_equal:date_to'],
            'date_to' => ['sometimes', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'sort' => ['sometimes', Rule::in(['newest', 'oldest', 'total_asc', 'total_desc', 'order_asc', 'order_desc'])],
        ];
    }
}

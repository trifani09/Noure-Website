<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;
use Illuminate\Validation\Rule;

class ListProductsRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'search' => ['sometimes', 'string', 'min:1', 'max:200'],
            'category' => ['sometimes', 'string', 'max:180'],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'archived'])],
            'availability' => ['sometimes', Rule::in(['available', 'unavailable'])],
            'min_price' => ['sometimes', 'integer', 'min:0', 'lte:max_price'],
            'max_price' => ['sometimes', 'integer', 'min:0', 'gte:min_price'],
            'sort' => ['sometimes', Rule::in(['newest', 'oldest', 'price_asc', 'price_desc', 'name_asc', 'name_desc'])],
        ];
    }
}

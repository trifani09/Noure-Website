<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;
use Illuminate\Validation\Rule;

class ListCustomersRequest extends CatalogRequest
{
    public function rules(): array
    {
        return ['page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'], 'search' => ['sometimes', 'string', 'max:100'], 'status' => ['sometimes', Rule::in(['active', 'disabled'])], 'sort' => ['sometimes', Rule::in(['newest', 'oldest', 'name_asc', 'name_desc', 'orders_desc', 'spent_desc'])]];
    }
}

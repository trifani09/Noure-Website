<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;
use Illuminate\Validation\Rule;

class ListDiscountsRequest extends CatalogRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'], 'search' => ['sometimes', 'string', 'max:100'], 'is_active' => ['sometimes', 'boolean'], 'sort' => ['sometimes', Rule::in(['newest', 'oldest', 'code_asc', 'code_desc'])]];
    }
}

<?php

namespace App\Http\Requests\Api\V1\Admin;

use Illuminate\Validation\Rule;

class UpdateProductRequest extends StoreProductRequest
{
    public function rules(): array
    {
        $rules = $this->productRules(true);
        $rules['slug'] = ['sometimes', 'filled', 'string', 'max:220', Rule::unique('products', 'slug')->whereNull('deleted_at')->ignore($this->route('public_id'), 'public_id')];

        return $rules;
    }
}

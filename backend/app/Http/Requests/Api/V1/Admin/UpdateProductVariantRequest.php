<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;
use Illuminate\Validation\Validator;

class UpdateProductVariantRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'sku' => ['sometimes', 'filled', 'string', 'max:100'],
            'title' => ['sometimes', 'nullable', 'string', 'max:255'],
            'option_values' => ['sometimes', 'array', 'min:1'],
            'option_values.*' => ['required', 'string', 'max:120'],
            'price_amount' => ['sometimes', 'integer', 'min:0'],
            'compare_at_amount' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'currency' => ['sometimes', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'barcode' => ['sometimes', 'nullable', 'string', 'max:100'],
            'weight_grams' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);
        $validator->after(function (Validator $validator): void {
            if ($this->has('combination_key') || $this->has('is_default')) {
                $validator->errors()->add($this->has('combination_key') ? 'combination_key' : 'is_default', 'This field is not writable.');
            }
        });
    }
}

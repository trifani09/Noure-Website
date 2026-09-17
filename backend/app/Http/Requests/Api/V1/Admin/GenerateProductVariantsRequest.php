<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;
use Illuminate\Validation\Validator;

class GenerateProductVariantsRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'option_values' => ['required', 'array', 'min:1'],
            'option_values.*' => ['required', 'array', 'min:1'],
            'option_values.*.*' => ['required', 'string', 'max:120', 'distinct'],
            'defaults' => ['required', 'array'],
            'defaults.price_amount' => ['required', 'integer', 'min:0'],
            'defaults.compare_at_amount' => ['nullable', 'integer', 'min:0'],
            'defaults.currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'defaults.weight_grams' => ['nullable', 'integer', 'min:0'],
            'defaults.is_active' => ['required', 'boolean'],
            'sku_template' => ['required', 'string', 'max:100'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);
        $validator->after(function (Validator $validator): void {
            $price = $this->input('defaults.price_amount');
            $compareAt = $this->input('defaults.compare_at_amount');
            if ($compareAt !== null && is_numeric($price) && $compareAt <= $price) {
                $validator->errors()->add('defaults.compare_at_amount', 'The compare-at amount must exceed the price.');
            }
            if ($this->has('defaults.is_default')) {
                $validator->errors()->add('defaults.is_default', 'Generated variants cannot be made default.');
            }
        });
    }
}

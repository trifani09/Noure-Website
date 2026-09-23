<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ListProductsRequest extends CatalogRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'category' => ['sometimes', 'filled', 'string', 'max:180'],
            'search' => ['sometimes', 'filled', 'string', 'max:200'],
            'min_price' => ['sometimes', 'integer', 'min:0'],
            'max_price' => ['sometimes', 'integer', 'min:0'],
            'availability' => ['sometimes', Rule::in(['available', 'unavailable'])],
            'color' => ['sometimes', 'filled', 'string', 'max:120'],
            'size' => ['sometimes', 'filled', 'string', 'max:120'],
            'discounted' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', Rule::in(['newest', 'best_selling', 'oldest', 'price_asc', 'price_desc', 'name_asc', 'name_desc'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        foreach (['category', 'search', 'color', 'size'] as $key) {
            if (array_key_exists($key, $this->query())) {
                $this->merge([$key => trim((string) $this->query($key))]);
            }
        }
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->filled('min_price') && $this->filled('max_price')
                    && (int) $this->input('min_price') > (int) $this->input('max_price')) {
                    $validator->errors()->add('min_price', 'The minimum price must not exceed the maximum price.');
                }
            },
        ];
    }
}

<?php

namespace App\Http\Requests\Api\V1;

class ShowCategoryRequest extends CatalogRequest
{
    /** @return array<string, mixed> */
    public function rules(): array
    {
        return ['include_product_count' => ['sometimes', 'boolean']];
    }

    protected function prepareForValidation(): void
    {
        $value = $this->query('include_product_count');
        if (is_string($value) && in_array(strtolower($value), ['true', 'false'], true)) {
            $this->merge(['include_product_count' => strtolower($value) === 'true']);
        }
    }
}

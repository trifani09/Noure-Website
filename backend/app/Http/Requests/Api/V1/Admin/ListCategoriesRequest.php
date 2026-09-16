<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;
use Illuminate\Validation\Rule;

class ListCategoriesRequest extends CatalogRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'parent' => ['sometimes', 'filled', 'string', 'max:26'],
            'search' => ['sometimes', 'filled', 'string', 'max:160'],
            'is_active' => ['sometimes', 'boolean'],
            'sort' => ['sometimes', Rule::in(['position', 'name_asc', 'name_desc', 'newest', 'oldest'])],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (array_key_exists('search', $this->query())) {
            $this->merge(['search' => trim((string) $this->query('search'))]);
        }

        $value = $this->query('is_active');
        if (is_string($value) && in_array(strtolower($value), ['true', 'false'], true)) {
            $this->merge(['is_active' => strtolower($value) === 'true']);
        }
    }
}

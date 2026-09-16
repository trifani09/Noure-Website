<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends CatalogRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'parent_public_id' => ['sometimes', 'nullable', 'string', 'size:26', Rule::exists('categories', 'public_id')->whereNull('deleted_at')],
            'name' => ['required', 'string', 'min:1', 'max:160'],
            'slug' => ['sometimes', 'filled', 'string', 'max:180', Rule::unique('categories', 'slug')->whereNull('deleted_at')],
            'description' => ['sometimes', 'nullable', 'string'],
            'image_path' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if (is_string($this->input('name'))) {
            $this->merge(['name' => trim($this->input('name'))]);
        }
        if (is_string($this->input('slug'))) {
            $this->merge(['slug' => Str::slug($this->input('slug'))]);
        }
    }
}

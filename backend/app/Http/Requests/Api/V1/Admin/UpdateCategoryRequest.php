<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateCategoryRequest extends CatalogRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $category = Category::query()->where('public_id', $this->route('public_id'))->first();

        return [
            'parent_public_id' => ['sometimes', 'nullable', 'string', 'size:26', Rule::exists('categories', 'public_id')->whereNull('deleted_at')],
            'name' => ['sometimes', 'filled', 'string', 'max:160'],
            'slug' => ['sometimes', 'filled', 'string', 'max:180', Rule::unique('categories', 'slug')->whereNull('deleted_at')->ignore($category?->getKey())],
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

    public function after(): array
    {
        return [function (Validator $validator): void {
            $writable = ['parent_public_id', 'name', 'slug', 'description', 'image_path', 'sort_order', 'is_active'];
            if (count(array_intersect($writable, array_keys($this->all()))) === 0) {
                $validator->errors()->add('category', 'At least one writable field is required.');
            }

            if (! $this->exists('parent_public_id') || $this->input('parent_public_id') === null) {
                return;
            }

            $category = Category::query()->where('public_id', $this->route('public_id'))->first();
            $parent = Category::query()->where('public_id', $this->input('parent_public_id'))->first();
            while ($category !== null && $parent !== null) {
                if ($parent->is($category)) {
                    $validator->errors()->add('parent_public_id', 'A category cannot be its own parent or descendant.');

                    return;
                }
                $parent = $parent->parent;
            }
        }];
    }
}

<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreHomepageSectionRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::in(['hero_banner', 'featured_categories', 'featured_products', 'promotional_banner', 'brand_story'])],
            'is_active' => ['required', 'boolean'], 'sort_order' => ['required', 'integer', 'min:0'],
            'configuration' => ['present', 'array'],
            'configuration.heading' => ['sometimes', 'nullable', 'string', 'max:255'],
            'configuration.body' => ['sometimes', 'nullable', 'string', 'max:5000'],
            'configuration.placement' => ['sometimes', 'nullable', 'string', 'max:100'],
            'configuration.cta_label' => ['sometimes', 'nullable', 'string', 'max:120'],
            'configuration.cta_url' => ['sometimes', 'nullable', 'string', 'max:2048', 'regex:/^(\/[^\s]*|https?:\/\/[^\s]+)$/i'],
            'category_public_ids' => ['present', 'array'], 'category_public_ids.*' => ['string', 'distinct', Rule::exists('categories', 'public_id')->whereNull('deleted_at')],
            'product_public_ids' => ['present', 'array'], 'product_public_ids.*' => ['string', 'distinct', Rule::exists('products', 'public_id')->whereNull('deleted_at')],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);
        $validator->after(function (Validator $validator): void {
            if ($this->input('type') === 'featured_categories' && count($this->input('category_public_ids', [])) === 0) {
                $validator->errors()->add('category_public_ids', 'Select at least one category.');
            }
            if ($this->input('type') === 'featured_products' && count($this->input('product_public_ids', [])) === 0) {
                $validator->errors()->add('product_public_ids', 'Select at least one product.');
            }
            if (in_array($this->input('type'), ['hero_banner', 'promotional_banner'], true) && ! $this->input('configuration.placement')) {
                $validator->errors()->add('configuration.placement', 'A banner placement is required.');
            }
        });
    }
}

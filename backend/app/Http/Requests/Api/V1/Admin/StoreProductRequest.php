<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProductRequest extends CatalogRequest
{
    public function rules(): array
    {
        return $this->productRules(false);
    }

    protected function productRules(bool $updating): array
    {
        $required = $updating ? 'sometimes' : 'required';

        return [
            'name' => [$required, 'string', 'min:1', 'max:200'],
            'slug' => ['sometimes', 'filled', 'string', 'max:220', Rule::unique('products', 'slug')->whereNull('deleted_at')],
            'short_description' => ['sometimes', 'nullable', 'string', 'max:500'],
            'description' => ['sometimes', 'nullable', 'string'],
            'brand' => ['sometimes', 'nullable', 'string', 'max:160'],
            'status' => [$required, Rule::in(['draft', 'active', 'archived'])],
            'published_at' => ['sometimes', 'nullable', 'date'],
            'metadata' => ['sometimes', 'nullable', 'array'],
            'categories' => [$updating ? 'sometimes' : 'present', 'array'],
            'categories.*.category_public_id' => ['required', 'string', 'distinct', Rule::exists('categories', 'public_id')->whereNull('deleted_at')],
            'categories.*.is_primary' => ['required', 'boolean'],
            'categories.*.sort_order' => ['required', 'integer', 'min:0'],
            'images' => [$updating ? 'sometimes' : 'present', 'array'],
            'images.*.path' => ['required', 'string', 'max:2048'],
            'images.*.alt_text' => ['nullable', 'string', 'max:255'],
            'images.*.width' => ['nullable', 'integer', 'min:0'],
            'images.*.height' => ['nullable', 'integer', 'min:0'],
            'images.*.mime_type' => ['nullable', 'string', 'max:100'],
            'images.*.sort_order' => ['required', 'integer', 'min:0'],
            'images.*.is_primary' => ['required', 'boolean'],
            'images.*.variant_sku' => ['nullable', 'string', 'max:100'],
            'options' => [$updating ? 'sometimes' : 'present', 'array'],
            'options.*.name' => ['required', 'string', 'max:100'],
            'options.*.code' => ['required', 'string', 'max:100'],
            'options.*.sort_order' => ['required', 'integer', 'min:0'],
            'options.*.values' => ['required', 'array', 'min:1'],
            'options.*.values.*.label' => ['required', 'string', 'max:120'],
            'options.*.values.*.code' => ['required', 'string', 'max:120'],
            'options.*.values.*.swatch_value' => ['nullable', 'string', 'max:100'],
            'options.*.values.*.sort_order' => ['required', 'integer', 'min:0'],
            'variants' => [$required, 'array', 'min:1'],
            'variants.*.public_id' => ['sometimes', 'string', 'size:26'],
            'variants.*.sku' => ['required', 'string', 'max:100'],
            'variants.*.title' => ['nullable', 'string', 'max:255'],
            'variants.*.option_values' => ['required', 'array'],
            'variants.*.price_amount' => ['required', 'integer', 'min:0'],
            'variants.*.compare_at_amount' => ['nullable', 'integer', 'min:0'],
            'variants.*.currency' => ['required', 'string', 'size:3', 'regex:/^[A-Z]{3}$/'],
            'variants.*.barcode' => ['nullable', 'string', 'max:100'],
            'variants.*.weight_grams' => ['nullable', 'integer', 'min:0'],
            'variants.*.is_active' => ['required', 'boolean'],
            'variants.*.is_default' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();
        if (isset($data['name']) && is_string($data['name'])) {
            $data['name'] = trim($data['name']);
        }
        if (isset($data['slug']) && is_string($data['slug'])) {
            $data['slug'] = Str::slug($data['slug']);
        }
        foreach ($data['options'] ?? [] as &$option) {
            if (isset($option['code'])) {
                $option['code'] = Str::slug($option['code']);
            }
            foreach ($option['values'] ?? [] as &$value) {
                if (isset($value['code'])) {
                    $value['code'] = Str::slug($value['code']);
                }
            }
        }
        $this->replace($data);
    }

    protected function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $data = $this->all();
            if (array_key_exists('categories', $data) && count(array_filter($data['categories'], fn ($c) => $c['is_primary'] ?? false)) !== (count($data['categories']) ? 1 : 0)) {
                $validator->errors()->add('categories', 'Categories must have exactly one primary category.');
            }
            if (! array_key_exists('variants', $data)) {
                return;
            }
            $defaults = array_filter($data['variants'], fn ($v) => $v['is_default'] ?? false);
            if (count($defaults) !== 1) {
                $validator->errors()->add('variants', 'Exactly one variant must be default.');
            } elseif (! ($defaults[array_key_first($defaults)]['is_active'] ?? false)) {
                $validator->errors()->add('variants', 'The default variant must be active.');
            }
            $currencies = array_unique(array_column($data['variants'], 'currency'));
            if (count($currencies) > 1) {
                $validator->errors()->add('variants', 'All variants must use the same currency.');
            }
            $optionMap = [];
            foreach ($data['options'] ?? [] as $option) {
                $optionMap[$option['code']] = array_column($option['values'], 'code');
            }
            $keys = [];
            foreach ($data['variants'] as $index => $variant) {
                if (($variant['compare_at_amount'] ?? null) !== null && $variant['compare_at_amount'] <= $variant['price_amount']) {
                    $validator->errors()->add("variants.$index.compare_at_amount", 'The compare-at amount must exceed the price.');
                }
                $selected = $variant['option_values'];
                ksort($selected);
                if (array_keys($selected) !== array_keys($optionMap)) {
                    $validator->errors()->add("variants.$index.option_values", 'Select exactly one value for every product option.');
                }
                foreach ($selected as $code => $value) {
                    if (! isset($optionMap[$code]) || ! in_array($value, $optionMap[$code], true)) {
                        $validator->errors()->add("variants.$index.option_values.$code", 'The selected option value is invalid.');
                    }
                }
                $key = json_encode($selected);
                if (in_array($key, $keys, true)) {
                    $validator->errors()->add("variants.$index.option_values", 'Variant option combinations must be unique.');
                }
                $keys[] = $key;
            }
        });
    }
}

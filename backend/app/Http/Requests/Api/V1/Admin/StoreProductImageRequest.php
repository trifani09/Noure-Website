<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;

class StoreProductImageRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'variant_public_id' => ['nullable', 'string', 'size:26'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }
}

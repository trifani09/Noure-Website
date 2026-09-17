<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;

class UpdateProductImageRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'alt_text' => ['sometimes', 'nullable', 'string', 'max:255'],
            'variant_public_id' => ['sometimes', 'nullable', 'string', 'size:26'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }
}

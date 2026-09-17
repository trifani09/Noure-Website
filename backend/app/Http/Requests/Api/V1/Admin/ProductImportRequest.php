<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;

class ProductImportRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'products' => ['required', 'file', 'max:10240', 'extensions:csv,xlsx'],
            'variants' => ['required', 'file', 'max:10240', 'extensions:csv,xlsx'],
            'images' => ['nullable', 'file', 'max:10240', 'extensions:csv,xlsx'],
            'media_files' => ['sometimes', 'array', 'max:100'],
            'media_files.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}

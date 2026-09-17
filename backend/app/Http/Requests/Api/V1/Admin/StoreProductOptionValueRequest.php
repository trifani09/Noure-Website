<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;
use Illuminate\Support\Str;

class StoreProductOptionValueRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'label' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:120'],
            'swatch_value' => ['nullable', 'string', 'max:100'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('code') && is_string($this->input('code'))) {
            $this->merge(['code' => Str::slug($this->string('code')->toString())]);
        }
    }
}

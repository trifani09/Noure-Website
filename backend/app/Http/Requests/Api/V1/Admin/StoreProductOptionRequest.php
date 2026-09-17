<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;
use Illuminate\Support\Str;

class StoreProductOptionRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:100'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'values' => ['required', 'array', 'min:1'],
            'values.*.label' => ['required', 'string', 'max:120'],
            'values.*.code' => ['required', 'string', 'max:120', 'distinct'],
            'values.*.swatch_value' => ['nullable', 'string', 'max:100'],
            'values.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $data = $this->all();
        if (isset($data['code']) && is_string($data['code'])) {
            $data['code'] = Str::slug($data['code']);
        }
        foreach ($data['values'] ?? [] as &$value) {
            if (isset($value['code']) && is_string($value['code'])) {
                $value['code'] = Str::slug($value['code']);
            }
        }
        $this->replace($data);
    }
}

<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;
use Illuminate\Validation\Validator;

class AdjustInventoryRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'location_code' => ['required', 'string', 'max:100'],
            'quantity_delta' => ['required', 'integer', 'not_in:0'],
            'reason' => ['required', 'string', 'max:500'],
            'reference_type' => ['nullable', 'string', 'max:100', 'required_with:reference_id'],
            'reference_id' => ['nullable', 'string', 'max:100', 'required_with:reference_type'],
            'expected_version' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);
        $validator->after(function (Validator $validator): void {
            foreach (['available', 'on_hand', 'reserved', 'safety_stock'] as $field) {
                if ($this->has($field)) {
                    $validator->errors()->add($field, 'This field is read-only.');
                }
            }
        });
    }
}

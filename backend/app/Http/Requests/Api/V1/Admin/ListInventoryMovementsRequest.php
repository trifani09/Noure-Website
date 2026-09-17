<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ListInventoryMovementsRequest extends CatalogRequest
{
    public function rules(): array
    {
        return [
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'location_code' => ['sometimes', 'string', 'max:100'],
            'movement_type' => ['sometimes', Rule::in(['receipt', 'adjustment', 'reservation', 'release', 'sale', 'return'])],
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
            'sort' => ['sometimes', Rule::in(['newest', 'oldest'])],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        parent::withValidator($validator);
        $validator->after(function (Validator $validator): void {
            if ($this->filled('from') && $this->filled('to') && strtotime((string) $this->input('to')) < strtotime((string) $this->input('from'))) {
                $validator->errors()->add('to', 'The end time must be after or equal to the start time.');
            }
        });
    }
}

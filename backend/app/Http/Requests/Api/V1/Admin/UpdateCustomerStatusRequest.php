<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerStatusRequest extends CatalogRequest
{
    public function rules(): array
    {
        return ['status' => ['required', Rule::in(['active', 'disabled'])]];
    }
}

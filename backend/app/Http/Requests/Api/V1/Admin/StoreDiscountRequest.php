<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;
use Illuminate\Validation\Rule;

class StoreDiscountRequest extends CatalogRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['code' => ['required', 'string', 'max:100', 'alpha_dash', Rule::unique('discounts', 'code')], 'name' => ['required', 'string', 'max:160'], 'description' => ['nullable', 'string', 'max:2000'], 'type' => ['required', Rule::in(['percentage', 'fixed_amount'])], 'value' => ['required', 'integer', 'min:1'], 'currency' => ['nullable', 'required_if:type,fixed_amount', 'string', 'size:3'], 'minimum_order_amount' => ['nullable', 'integer', 'min:0'], 'maximum_discount_amount' => ['nullable', 'integer', 'min:0'], 'usage_limit' => ['nullable', 'integer', 'min:1'], 'usage_limit_per_customer' => ['nullable', 'integer', 'min:1'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at'], 'is_active' => ['required', 'boolean']];
    }
}

<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;
use App\Models\Discount;
use Illuminate\Validation\Rule;

class UpdateDiscountRequest extends CatalogRequest
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
        $discount = Discount::query()->where('public_id', $this->route('discount'))->first();

        return ['code' => ['sometimes', 'required', 'string', 'max:100', 'alpha_dash', Rule::unique('discounts', 'code')->ignore($discount?->id)], 'name' => ['sometimes', 'required', 'string', 'max:160'], 'description' => ['nullable', 'string', 'max:2000'], 'type' => ['sometimes', Rule::in(['percentage', 'fixed_amount'])], 'value' => ['sometimes', 'integer', 'min:1'], 'currency' => ['nullable', 'string', 'size:3'], 'minimum_order_amount' => ['nullable', 'integer', 'min:0'], 'maximum_discount_amount' => ['nullable', 'integer', 'min:0'], 'usage_limit' => ['nullable', 'integer', 'min:1'], 'usage_limit_per_customer' => ['nullable', 'integer', 'min:1'], 'starts_at' => ['nullable', 'date'], 'ends_at' => ['nullable', 'date', 'after:starts_at'], 'is_active' => ['sometimes', 'boolean']];
    }
}

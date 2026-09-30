<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id, 'code' => $this->code, 'name' => $this->name, 'description' => $this->description, 'type' => $this->type, 'value' => (int) $this->value, 'currency' => $this->currency,
            'minimum_order_amount' => $this->minimum_order_amount, 'maximum_discount_amount' => $this->maximum_discount_amount, 'usage_limit' => $this->usage_limit, 'usage_limit_per_customer' => $this->usage_limit_per_customer, 'used_count' => (int) $this->used_count,
            'starts_at' => $this->starts_at?->utc()->toISOString(), 'ends_at' => $this->ends_at?->utc()->toISOString(), 'is_active' => (bool) $this->is_active, 'created_at' => $this->created_at?->utc()->toISOString(), 'updated_at' => $this->updated_at?->utc()->toISOString(),
        ];
    }
}

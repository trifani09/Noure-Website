<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id, 'email' => $this->email, 'phone' => $this->phone,
            'first_name' => $this->first_name, 'last_name' => $this->last_name, 'name' => trim($this->first_name.' '.$this->last_name), 'status' => $this->status,
            'marketing_consent_at' => $this->marketing_consent_at?->utc()->toISOString(), 'last_order_at' => $this->last_order_at?->utc()->toISOString(),
            'orders_count' => (int) ($this->orders_count ?? 0), 'total_spent' => (int) ($this->total_spent ?? 0), 'created_at' => $this->created_at?->utc()->toISOString(),
            'addresses' => $this->whenLoaded('addresses', fn () => $this->addresses->map(fn ($address) => ['public_id' => $address->public_id, 'label' => $address->label, 'recipient_name' => $address->recipient_name, 'phone' => $address->phone, 'line1' => $address->line1, 'line2' => $address->line2, 'city' => $address->city, 'province' => $address->province, 'postal_code' => $address->postal_code, 'country_code' => $address->country_code])),
        ];
    }
}

<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminOrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $name = $this->customer ? trim($this->customer->first_name.' '.$this->customer->last_name) : ($this->shipping_address['recipient_name'] ?? 'Guest');

        return [
            'public_id' => $this->public_id,
            'order_number' => $this->order_number,
            'customer' => ['name' => $name, 'email' => $this->email, 'phone' => $this->phone],
            'grand_total_amount' => $this->grand_total_amount,
            'currency' => $this->currency,
            'payment_status' => $this->payment_status,
            'fulfillment_status' => $this->fulfillment_status,
            'status' => $this->status,
            'created_at' => $this->created_at?->utc()->toISOString(),
        ];
    }
}

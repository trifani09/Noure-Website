<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerOrderListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'order_number' => $this->order_number,
            'created_at' => $this->created_at?->utc()->toISOString(),
            'item_count' => $this->items->sum('quantity'),
            'grand_total_amount' => $this->grand_total_amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'fulfillment_status' => $this->fulfillment_status,
        ];
    }
}
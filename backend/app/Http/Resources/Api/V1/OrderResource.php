<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id, 'order_number' => $this->order_number, 'status' => $this->status,
            'payment_status' => $this->payment_status, 'fulfillment_status' => $this->fulfillment_status,
            'email' => $this->email, 'phone' => $this->phone, 'shipping_address' => $this->shipping_address,
            'items' => $this->items->map(fn ($item): array => [
                'product_name' => $item->product_name, 'variant_name' => $item->variant_name, 'sku' => $item->sku,
                'option_values' => $item->option_values, 'quantity' => $item->quantity,
                'unit_price_amount' => $item->unit_price_amount, 'total_amount' => $item->total_amount, 'currency' => $item->currency,
            ])->values(),
            'subtotal_amount' => $this->subtotal_amount, 'discount_amount' => $this->discount_amount,
            'shipping_amount' => $this->shipping_amount, 'grand_total_amount' => $this->grand_total_amount,
            'currency' => $this->currency, 'placed_at' => $this->placed_at?->toISOString(),
            'shipping_method' => ($this->metadata ?? [])['shipping'] ?? null,
        ];
    }
}

<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;

class AdminOrderResource extends AdminOrderListResource
{
    public function toArray(Request $request): array
    {
        $data = parent::toArray($request);
        $history = [[
            'from' => null, 'to' => 'pending', 'changed_at' => $this->placed_at?->utc()->toISOString(), 'actor' => null,
        ], ...($this->metadata['status_history'] ?? [])];

        return [...$data,
            'subtotal_amount' => $this->subtotal_amount,
            'discount_amount' => $this->discount_amount,
            'shipping_amount' => $this->shipping_amount,
            'tax_amount' => $this->tax_amount,
            'shipping_address' => $this->shipping_address,
            'billing_address' => $this->billing_address,
            'items' => $this->items->map(fn ($item): array => [
                'product_name' => $item->product_name, 'variant_name' => $item->variant_name, 'sku' => $item->sku,
                'option_values' => $item->option_values, 'quantity' => $item->quantity,
                'unit_price_amount' => $item->unit_price_amount, 'total_amount' => $item->total_amount, 'currency' => $item->currency,
            ])->values(),
            'payment' => ['status' => $this->payment_status, 'provider' => null, 'transactions' => []],
            'status_history' => $history,
        ];
    }
}

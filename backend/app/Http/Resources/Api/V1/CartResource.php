<?php

namespace App\Http\Resources\Api\V1;

use App\Discounts\DiscountException;
use App\Discounts\DiscountService;
use App\Models\Customer;
use App\Models\StoreSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $lowStockThreshold = StoreSetting::query()->value('low_stock_threshold') ?? 5;
        $items = $this->items->map(function ($item) use ($lowStockThreshold): array {
            $variant = $item->variant;
            $product = $variant->product;
            $image = $product->images->firstWhere('variant_id', $variant->id)
                ?? $product->images->firstWhere('is_primary', true)
                ?? $product->images->sortBy('sort_order')->first();
            $subtotal = $variant->price_amount * $item->quantity;
            $availableQuantity = $variant->inventoryLevels
                ->filter(fn ($level) => $level->location?->is_active)
                ->sum(fn ($level) => max($level->on_hand - $level->reserved - $level->safety_stock, 0));
            $inventoryStatus = match (true) {
                $availableQuantity < 1 => 'out_of_stock',
                $availableQuantity <= $lowStockThreshold => 'low_stock',
                default => 'in_stock',
            };

            return [
                'id' => $item->id,
                'quantity' => $item->quantity,
                'unit_price_amount' => $variant->price_amount,
                'subtotal_amount' => $subtotal,
                'currency' => $variant->currency,
                'product' => ['public_id' => $product->public_id, 'name' => $product->name, 'slug' => $product->slug],
                'variant' => [
                    'public_id' => $variant->public_id,
                    'sku' => $variant->sku,
                    'title' => $variant->title,
                    'available_quantity' => $availableQuantity,
                    'inventory_status' => $inventoryStatus,
                    'selected_options' => $variant->optionValues->sortBy('option.sort_order')->map(fn ($value) => [
                        'option_code' => $value->option->code,
                        'option_name' => $value->option->name,
                        'value_code' => $value->code,
                        'value_label' => $value->label,
                    ])->values(),
                ],
                'image' => $image ? ['url' => Storage::disk('public')->url($image->path), 'alt_text' => $image->alt_text] : null,
            ];
        })->values();
        $subtotal = $items->sum('subtotal_amount');
        $appliedDiscount = null;
        try {
            /** @var Customer|null $customer */
            $customer = Auth::guard('customer')->user();
            $quote = app(DiscountService::class)->quote($this->resource, $customer);
            $appliedDiscount = ['code' => $quote['code'], 'name' => $quote['name'], 'amount' => $quote['amount']];
        } catch (DiscountException) {
            // An invalid or expired code is revalidated before order creation.
        }
        $discountAmount = $appliedDiscount['amount'] ?? 0;

        return [
            'public_id' => $this->public_id,
            'items' => $items,
            'item_count' => $items->sum('quantity'),
            'subtotal_amount' => $subtotal,
            'discount_amount' => $discountAmount,
            'total_amount' => $subtotal - $discountAmount,
            'applied_discount' => $appliedDiscount,
            'currency' => $this->currency,
        ];
    }
}

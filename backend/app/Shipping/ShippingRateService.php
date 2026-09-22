<?php

namespace App\Shipping;

use App\Models\Cart;
use App\Models\ProductVariant;

class ShippingRateService
{
    public function __construct(private readonly RuleBasedShippingProvider $provider) {}

    /** @return array<int, array<string, mixed>> */
    public function availableMethods(Cart $cart, array $destination): array
    {
        return $this->provider->availableMethods($cart, $destination);
    }

    /** @return array<string, mixed> */
    public function calculate(Cart $cart, array $destination, string $methodCode): array
    {
        return $this->provider->calculate($cart, $destination, $methodCode);
    }

    public function cartWeight(Cart $cart): int
    {
        $cart->loadMissing('items.variant');

        return $cart->items->sum(fn ($item): int => ((int) ($item->variant->weight_grams ?? 0)) * $item->quantity);
    }
}
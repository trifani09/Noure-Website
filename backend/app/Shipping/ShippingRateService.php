<?php

namespace App\Shipping;

use App\Models\Cart;

class ShippingRateService
{
    private readonly ShippingProviderInterface $provider;

    public function __construct(RuleBasedShippingProvider $fallback, BiteshipShippingProvider $biteship)
    {
        $this->provider = config('shipping.provider') === 'biteship'
            && filled(config('shipping.biteship.token'))
            && filled(config('shipping.biteship.origin_postal_code'))
            ? $biteship
            : $fallback;
    }

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

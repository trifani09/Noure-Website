<?php

namespace App\Shipping;

use App\Models\Cart;

interface ShippingProviderInterface
{
    public function availableMethods(Cart $cart, array $destination): array;

    public function calculate(Cart $cart, array $destination, string $methodCode): array;
}

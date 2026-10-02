<?php

namespace App\Shipping;

use App\Checkout\CheckoutException;
use App\Models\Cart;
use Illuminate\Support\Facades\Http;

class BiteshipShippingProvider implements ShippingProviderInterface
{
    public function availableMethods(Cart $cart, array $destination): array
    {
        $postalCode = $destination['postal_code'] ?? null;
        if (! $postalCode) {
            throw new CheckoutException('postal_code_required', 'A postal code is required to calculate live shipping rates.', 422);
        }
        $cart->loadMissing('items.variant.product');
        $response = Http::baseUrl(config('shipping.biteship.base_url'))->withHeaders(['Authorization' => config('shipping.biteship.token')])->timeout(10)->retry(2, 250)->post('/v1/rates/couriers', [
            'origin_postal_code' => (int) config('shipping.biteship.origin_postal_code'),
            'destination_postal_code' => (int) $postalCode,
            'couriers' => config('shipping.biteship.couriers'),
            'items' => $cart->items->map(fn ($item) => ['name' => $item->variant->product->name, 'description' => $item->variant->title, 'value' => $item->variant->price_amount, 'weight' => max(1, (int) ($item->variant->weight_grams ?? 1)), 'quantity' => $item->quantity])->values()->all(),
        ]);
        if ($response->failed()) {
            throw new CheckoutException('shipping_provider_unavailable', 'Live shipping rates are temporarily unavailable.', 503);
        }

        return collect($response->json('pricing', []))->map(fn (array $rate) => ['code' => $rate['courier_code'].':'.$rate['courier_service_code'], 'name' => $rate['courier_name'].' '.$rate['courier_service_name'], 'description' => $rate['description'] ?? null, 'amount' => (int) $rate['price'], 'currency' => 'IDR', 'estimate' => $rate['duration'] ?? null, 'courier_code' => $rate['courier_code'], 'service_code' => $rate['courier_service_code'], 'weight_grams' => $cart->items->sum(fn ($item) => ((int) ($item->variant->weight_grams ?? 0)) * $item->quantity), 'free_shipping' => false])->all();
    }

    public function calculate(Cart $cart, array $destination, string $methodCode): array
    {
        $method = collect($this->availableMethods($cart, $destination))->firstWhere('code', $methodCode);
        if (! $method) {
            throw new CheckoutException('shipping_method_unavailable', 'The selected shipping method is unavailable.', 422);
        }

        return $method;
    }
}

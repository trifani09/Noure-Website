<?php

namespace App\Shipping;

use App\Checkout\CheckoutException;
use App\Models\Cart;
use Illuminate\Support\Str;

class RuleBasedShippingProvider
{
    /** @return array<int, array<string, mixed>> */
    public function availableMethods(Cart $cart, array $destination): array
    {
        $zone = $this->zone($destination);
        $subtotal = $this->subtotal($cart);

        return collect(config('shipping.methods'))->filter(fn (array $method): bool => $method['active'] ?? false)
            ->map(fn (array $method, string $code): array => $this->rate($cart, $destination, $code, $method, $zone, $subtotal))
            ->values()->all();
    }

    /** @return array<string, mixed> */
    public function calculate(Cart $cart, array $destination, string $methodCode): array
    {
        $method = config('shipping.methods.'.$methodCode);
        if (! is_array($method) || ! ($method['active'] ?? false)) {
            throw new CheckoutException('shipping_method_unavailable', 'The selected shipping method is unavailable.', 422);
        }
        $zone = $this->zone($destination);

        return $this->rate($cart, $destination, $methodCode, $method, $zone, $this->subtotal($cart));
    }

    /** @return array<string, mixed> */
    private function rate(Cart $cart, array $destination, string $code, array $method, array $zone, int $subtotal): array
    {
        $free = $subtotal >= (int) config('shipping.free_shipping_min_order');
        $weightSteps = (int) ceil(max(0, $this->weight($cart) - 1) / (int) config('shipping.weight_step_grams'));
        $amount = $free ? 0 : (int) $method['base_price'] + ($weightSteps * (int) config('shipping.weight_surcharge_per_step'));

        return [
            'code' => $code,
            'name' => $method['name'],
            'description' => $method['description'],
            'amount' => $amount,
            'currency' => config('shipping.currency'),
            'estimate' => $method['estimate'],
            'zone' => ['code' => $zone['code'], 'name' => $zone['name']],
            'weight_grams' => $this->weight($cart),
            'free_shipping' => $free,
        ];
    }

    /** @return array{code: string, name: string} */
    private function zone(array $destination): array
    {
        if (strtoupper((string) ($destination['country_code'] ?? '')) !== 'ID') {
            throw new CheckoutException('unsupported_destination', 'Shipping is currently available only within Indonesia.', 422);
        }
        $city = $this->normalize($destination['city'] ?? '');
        $province = $this->normalize($destination['province'] ?? '');
        foreach (config('shipping.zones') as $code => $zone) {
            if (in_array($city, $zone['cities'] ?? [], true) || in_array($province, $zone['provinces'] ?? [], true)) {
                return ['code' => $code, 'name' => $zone['name']];
            }
        }
        if ($fallback = config('shipping.zones.other_indonesia')) {
            return ['code' => 'other_indonesia', 'name' => $fallback['name']];
        }
        throw new CheckoutException('unsupported_destination', 'We do not currently deliver to this destination.', 422);
    }

    private function subtotal(Cart $cart): int
    {
        $cart->loadMissing('items.variant');

        return (int) $cart->items->sum(fn ($item): int => $item->variant->price_amount * $item->quantity);
    }

    private function weight(Cart $cart): int
    {
        $cart->loadMissing('items.variant');

        return (int) $cart->items->sum(fn ($item): int => ((int) ($item->variant->weight_grams ?? 0)) * $item->quantity);
    }

    private function normalize(string $value): string
    {
        return Str::of($value)->lower()->ascii()->replace(['kabupaten ', 'kota '], '')->squish()->toString();
    }
}
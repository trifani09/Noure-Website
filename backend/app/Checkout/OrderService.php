<?php

namespace App\Checkout;

use App\Events\OrderCreated;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\InventoryLevel;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderService
{
    /** @param array<string, mixed> $input */
    public function create(Cart $cart, ?Customer $customer, array $input): Order
    {
        return DB::transaction(function () use ($cart, $customer, $input): Order {
            $cart = Cart::query()->whereKey($cart->id)->where('status', 'active')->lockForUpdate()->first();
            if (! $cart) {
                throw new CheckoutException('cart_expired', 'Your cart session has expired.');
            }
            $items = $cart->items()->lockForUpdate()->get();
            if ($items->isEmpty()) {
                throw new CheckoutException('empty_cart', 'Your cart is empty.', 422);
            }
            $variants = ProductVariant::withTrashed()->with(['product', 'optionValues.option'])
                ->whereIn('id', $items->pluck('variant_id'))->lockForUpdate()->get()->keyBy('id');
            $subtotal = 0;
            foreach ($items as $item) {
                $variant = $variants->get($item->variant_id);
                if ($item->quantity < 1) {
                    throw new CheckoutException('invalid_quantity', 'An item in your cart has an invalid quantity.', 422);
                }
                if (! $variant || $variant->trashed() || ! $variant->product) {
                    throw new CheckoutException('variant_not_found', 'An item in your cart no longer exists.');
                }
                if (! $variant->is_active) {
                    throw new CheckoutException('variant_inactive', 'An item in your cart is no longer available.');
                }
                if ($variant->currency !== $cart->currency) {
                    throw new CheckoutException('currency_conflict', 'Cart items must use one currency.');
                }
                $subtotal += $variant->price_amount * $item->quantity;
            }

            $shippingAddress = $this->shippingAddress($customer, $input);
            $discount = 0;
            $shipping = 0;
            $tax = 0;
            $order = Order::query()->create([
                'order_number' => 'NOU-'.now()->format('Ymd').'-'.Str::upper(Str::random(8)),
                'cart_id' => $cart->id,
                'customer_id' => $customer?->id,
                'status' => 'pending',
                'payment_status' => 'unpaid',
                'fulfillment_status' => 'unfulfilled',
                'placed_at' => now(),
                'email' => $customer?->email ?? $input['email'],
                'phone' => $customer?->phone ?? $input['phone'],
                'currency' => $cart->currency,
                'subtotal_amount' => $subtotal,
                'discount_amount' => $discount,
                'shipping_amount' => $shipping,
                'tax_amount' => $tax,
                'grand_total_amount' => $subtotal - $discount + $shipping + $tax,
                'billing_address' => $shippingAddress,
                'shipping_address' => $shippingAddress,
                'metadata' => [],
            ]);
            foreach ($items as $item) {
                $variant = $variants->get($item->variant_id);
                $lineSubtotal = $variant->price_amount * $item->quantity;
                $order->items()->create([
                    'product_id' => $variant->product_id, 'variant_id' => $variant->id,
                    'product_name' => $variant->product->name, 'variant_name' => $variant->title, 'sku' => $variant->sku,
                    'option_values' => $variant->optionValues->sortBy('option.sort_order')->mapWithKeys(fn ($value) => [$value->option->name => $value->label])->all(),
                    'quantity' => $item->quantity, 'unit_price_amount' => $variant->price_amount,
                    'subtotal_amount' => $lineSubtotal, 'discount_amount' => 0, 'tax_amount' => 0,
                    'total_amount' => $lineSubtotal, 'currency' => $variant->currency,
                ]);
                $this->reserve($variant->id, $item->quantity, $order);
            }
            $cart->items()->delete();
            $cart->update(['status' => 'converted', 'converted_at' => now()]);
            if ($customer) {
                $customer->update(['last_order_at' => now()]);
            }

            $order = $order->load('items');
            OrderCreated::dispatch($order);

            return $order;
        });
    }

    /** @param array<string, mixed> $input
     * @return array<string, mixed>
     */
    private function shippingAddress(?Customer $customer, array $input): array
    {
        if ($customer && isset($input['address_public_id'])) {
            $address = Address::query()->where('customer_id', $customer->id)->where('public_id', $input['address_public_id'])->first();
            if (! $address) {
                throw new CheckoutException('invalid_address', 'The selected shipping address is not available.', 422);
            }

            return $this->addressSnapshot($address);
        }
        if ($customer && ! isset($input['shipping_address'])) {
            $address = $customer->addresses()->orderByDesc('is_default_shipping')->first();
            if (! $address) {
                throw new CheckoutException('invalid_address', 'Add a shipping address before placing your order.', 422);
            }

            return $this->addressSnapshot($address);
        }
        $address = $input['shipping_address'];

        return [
            'recipient_name' => $customer ? trim($customer->first_name.' '.$customer->last_name) : $input['name'],
            'phone' => $customer?->phone ?? $input['phone'], 'line1' => $address['line1'], 'line2' => $address['line2'] ?? null,
            'city' => $address['city'], 'province' => $address['province'] ?? null, 'postal_code' => $address['postal_code'],
            'country_code' => strtoupper($address['country_code'] ?? 'ID'),
        ];
    }

    /** @return array<string, mixed> */
    private function addressSnapshot(Address $address): array
    {
        return $address->only(['recipient_name', 'phone', 'line1', 'line2', 'city', 'province', 'postal_code', 'country_code']);
    }

    private function reserve(int $variantId, int $quantity, Order $order): void
    {
        $levels = InventoryLevel::query()->where('variant_id', $variantId)
            ->whereHas('location', fn ($query) => $query->where('is_active', true))->orderBy('id')->lockForUpdate()->get();
        $available = $levels->sum(fn (InventoryLevel $level): int => max($level->on_hand - $level->reserved - $level->safety_stock, 0));
        if ($available < $quantity) {
            throw new CheckoutException('insufficient_stock', 'An item in your cart is out of stock.');
        }
        $remaining = $quantity;
        foreach ($levels as $level) {
            $allocated = min($remaining, max($level->on_hand - $level->reserved - $level->safety_stock, 0));
            if ($allocated === 0) {
                continue;
            }
            $level->update(['reserved' => $level->reserved + $allocated, 'version' => $level->version + 1]);
            InventoryMovement::query()->create([
                'inventory_level_id' => $level->id, 'quantity_delta' => -$allocated, 'movement_type' => 'reservation',
                'reference_type' => 'order', 'reference_id' => $order->public_id, 'reason' => 'Reserved for order '.$order->order_number,
            ]);
            $remaining -= $allocated;
            if ($remaining === 0) {
                break;
            }
        }
    }
}

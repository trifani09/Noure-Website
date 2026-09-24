<?php

namespace App\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CartService
{
    public function resolve(?Customer $customer, ?string $guestToken): array
    {
        if ($customer) {
            $cart = Cart::query()->firstOrCreate(
                ['customer_id' => $customer->id, 'status' => 'active'],
                ['currency' => 'IDR', 'expires_at' => now()->addDays(30)],
            );

            return [$this->load($cart), null];
        }

        $cart = $guestToken ? Cart::query()->where('guest_token_hash', hash('sha256', $guestToken))->where('status', 'active')->where('expires_at', '>', now())->first() : null;
        if ($cart) {
            return [$this->load($cart), null];
        }

        $token = Str::random(64);
        $cart = Cart::query()->create(['guest_token_hash' => hash('sha256', $token), 'status' => 'active', 'currency' => 'IDR', 'expires_at' => now()->addDays(30)]);

        return [$this->load($cart), $token];
    }

    public function add(Cart $cart, string $variantPublicId, int $quantity): Cart
    {
        return DB::transaction(function () use ($cart, $variantPublicId, $quantity): Cart {
            $variant = $this->availableVariant($variantPublicId);
            $item = CartItem::query()->where('cart_id', $cart->id)->where('variant_id', $variant->id)->lockForUpdate()->first();
            $newQuantity = ($item?->quantity ?? 0) + $quantity;
            $this->ensureStock($variant, $newQuantity);
            if ($cart->items()->exists() && $cart->currency !== $variant->currency) {
                throw new CartException('currency_conflict', 'All cart items must use the same currency.', 409);
            }
            $cart->update(['currency' => $variant->currency, 'expires_at' => now()->addDays(30)]);
            CartItem::query()->updateOrCreate(
                ['cart_id' => $cart->id, 'variant_id' => $variant->id],
                ['quantity' => $newQuantity, 'unit_price_snapshot' => $variant->price_amount, 'currency' => $variant->currency],
            );

            return $this->load($cart);
        });
    }

    public function update(Cart $cart, int $itemId, int $quantity): Cart
    {
        return DB::transaction(function () use ($cart, $itemId, $quantity): Cart {
            $item = CartItem::query()->where('cart_id', $cart->id)->whereKey($itemId)->lockForUpdate()->first();
            if (! $item) {
                throw new CartException('cart_item_not_found', 'Cart item not found.', 404);
            }
            $variant = $this->availableVariant($item->variant->public_id);
            $this->ensureStock($variant, $quantity);
            $item->update(['quantity' => $quantity, 'unit_price_snapshot' => $variant->price_amount, 'currency' => $variant->currency]);

            return $this->load($cart);
        });
    }

    public function remove(Cart $cart, int $itemId): Cart
    {
        $deleted = $cart->items()->whereKey($itemId)->delete();
        if (! $deleted) {
            throw new CartException('cart_item_not_found', 'Cart item not found.', 404);
        }

        return $this->load($cart);
    }

    public function refresh(Cart $cart): Cart
    {
        return $this->load($cart->fresh());
    }

    private function availableVariant(string $publicId): ProductVariant
    {
        $variant = ProductVariant::query()->with(['product', 'inventoryLevels.location'])->where('public_id', $publicId)->where('is_active', true)->first();
        if (! $variant) {
            throw new CartException('variant_not_found', 'Variant not found.', 404);
        }
        if ($this->stock($variant) < 1) {
            throw new CartException('variant_unavailable', 'This variant is currently unavailable.', 409);
        }

        return $variant;
    }

    private function ensureStock(ProductVariant $variant, int $quantity): void
    {
        if ($quantity > $this->stock($variant)) {
            throw new CartException('insufficient_stock', 'The requested quantity is not available.', 409);
        }
    }

    private function stock(ProductVariant $variant): int
    {
        return $variant->inventoryLevels->filter(fn ($level) => $level->location?->is_active)->sum(fn ($level) => max($level->on_hand - $level->reserved - $level->safety_stock, 0));
    }

    private function load(Cart $cart): Cart
    {
        return $cart->load(['items.variant.product.images', 'items.variant.optionValues.option']);
    }
}

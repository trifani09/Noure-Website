<?php

namespace App\Discounts;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Discount;

class DiscountService
{
    /** @return array{discount: Discount, amount: int, code: string, name: string} */
    public function apply(Cart $cart, ?Customer $customer, string $code): array
    {
        $discount = Discount::query()->whereRaw('UPPER(code) = ?', [strtoupper($code)])->first();
        if (! $discount) {
            throw new DiscountException('discount_not_found', 'This discount code is not valid.');
        }
        $cart->update(['discount_id' => $discount->id]);

        try {
            return $this->quote($cart->fresh(), $customer);
        } catch (DiscountException $exception) {
            $cart->update(['discount_id' => null]);
            throw $exception;
        }
    }

    /** @return array{discount: Discount, amount: int, code: string, name: string} */
    public function quote(Cart $cart, ?Customer $customer, bool $lock = false): array
    {
        if (! $cart->discount_id) {
            throw new DiscountException('discount_missing', 'No discount code is applied.');
        }
        $query = Discount::query()->with(['products:id', 'categories:id'])->whereKey($cart->discount_id);
        $discount = $lock ? $query->lockForUpdate()->first() : $query->first();
        if (! $discount || ! $discount->is_active || ($discount->starts_at && $discount->starts_at->isFuture()) || ($discount->ends_at && $discount->ends_at->isPast())) {
            throw new DiscountException('discount_inactive', 'This discount code is not currently active.');
        }
        if ($discount->usage_limit !== null && $discount->used_count >= $discount->usage_limit) {
            throw new DiscountException('discount_limit_reached', 'This discount code has reached its usage limit.');
        }
        if ($customer && $discount->usage_limit_per_customer !== null && $discount->redemptions()->where('customer_id', $customer->id)->count() >= $discount->usage_limit_per_customer) {
            throw new DiscountException('discount_customer_limit_reached', 'You have already used this discount code.');
        }

        $cart->loadMissing('items.variant.product.categories');
        $subtotal = $cart->items->sum(fn ($item) => $item->variant->price_amount * $item->quantity);
        if ($discount->minimum_order_amount !== null && $subtotal < $discount->minimum_order_amount) {
            throw new DiscountException('discount_minimum_not_met', 'Your cart does not meet the minimum amount for this discount.');
        }
        if ($discount->type === 'fixed_amount' && $discount->currency !== null && $discount->currency !== $cart->currency) {
            throw new DiscountException('discount_currency_mismatch', 'This discount is not available for the cart currency.');
        }

        $productIds = $discount->products->pluck('id');
        $categoryIds = $discount->categories->pluck('id');
        $eligible = $cart->items->sum(function ($item) use ($productIds, $categoryIds): int {
            $product = $item->variant->product;
            $matches = $productIds->isEmpty() && $categoryIds->isEmpty()
                || $productIds->contains($product->id)
                || $product->categories->pluck('id')->intersect($categoryIds)->isNotEmpty();

            return $matches ? $item->variant->price_amount * $item->quantity : 0;
        });
        if ($eligible === 0) {
            throw new DiscountException('discount_not_applicable', 'This discount does not apply to the items in your cart.');
        }
        $amount = $discount->type === 'percentage'
            ? intdiv($eligible * $discount->value, 10000)
            : min($eligible, $discount->value);
        if ($discount->maximum_discount_amount !== null) {
            $amount = min($amount, $discount->maximum_discount_amount);
        }

        return ['discount' => $discount, 'amount' => $amount, 'code' => $discount->code, 'name' => $discount->name];
    }
}

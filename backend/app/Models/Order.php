<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['order_number', 'cart_id', 'customer_id', 'status', 'payment_status', 'fulfillment_status', 'placed_at', 'cancelled_at', 'email', 'phone', 'currency', 'subtotal_amount', 'discount_amount', 'shipping_amount', 'tax_amount', 'grand_total_amount', 'billing_address', 'shipping_address', 'customer_note', 'internal_note', 'metadata'])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, HasUlids;

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function discountRedemptions(): HasMany
    {
        return $this->hasMany(DiscountRedemption::class);
    }

    protected function casts(): array
    {
        return [
            'placed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'billing_address' => 'array',
            'shipping_address' => 'array',
            'metadata' => 'array',
            'subtotal_amount' => 'integer',
            'discount_amount' => 'integer',
            'shipping_amount' => 'integer',
            'tax_amount' => 'integer',
            'grand_total_amount' => 'integer',
        ];
    }
}

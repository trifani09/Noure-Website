<?php

namespace App\Models;

use Database\Factories\DiscountRedemptionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['discount_id', 'order_id', 'customer_id', 'code_snapshot', 'amount', 'currency'])]
class DiscountRedemption extends Model
{
    /** @use HasFactory<DiscountRedemptionFactory> */
    use HasFactory;

    public const UPDATED_AT = null;

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }
}

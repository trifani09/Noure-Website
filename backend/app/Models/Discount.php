<?php

namespace App\Models;

use Database\Factories\DiscountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['code', 'name', 'description', 'type', 'value', 'currency', 'minimum_order_amount', 'maximum_discount_amount', 'usage_limit', 'usage_limit_per_customer', 'used_count', 'starts_at', 'ends_at', 'is_active'])]
class Discount extends Model
{
    /** @use HasFactory<DiscountFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'discount_products');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'discount_categories');
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(DiscountRedemption::class);
    }

    protected function casts(): array
    {
        return [
            'value' => 'integer',
            'minimum_order_amount' => 'integer',
            'maximum_discount_amount' => 'integer',
            'usage_limit' => 'integer',
            'usage_limit_per_customer' => 'integer',
            'used_count' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }
}

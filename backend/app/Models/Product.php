<?php

namespace App\Models;

use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['name', 'slug', 'short_description', 'description', 'status', 'brand', 'published_at', 'metadata'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories')->withPivot(['is_primary', 'sort_order']);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function discounts(): BelongsToMany
    {
        return $this->belongsToMany(Discount::class, 'discount_products');
    }

    /** @param Builder<Product> $query */
    public function scopePubliclyVisible(Builder $query): void
    {
        $query
            ->where('status', 'active')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereHas('variants', fn (Builder $variants) => $variants
                ->where('is_active', true));
    }

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'metadata' => 'array'];
    }
}

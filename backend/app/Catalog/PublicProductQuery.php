<?php

namespace App\Catalog;

use App\Models\Category;
use App\Models\InventoryLevel;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;

class PublicProductQuery
{
    /**
     * @param  array{per_page?: int, category?: string, search?: string, min_price?: int, max_price?: int, availability?: string, color?: string, size?: string, discounted?: bool, sort?: string}  $filters
     * @return LengthAwarePaginator<Product>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = $this->baseQuery()->with($this->listingRelations());

        if (isset($filters['category'])) {
            $query->whereHas('categories', fn (Builder $categories) => $categories
                ->publiclyVisible()
                ->where('slug', $filters['category']));
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%'.$this->escapeLike($filters['search']).'%');
        }

        if (isset($filters['min_price']) || isset($filters['max_price'])) {
            $query->whereHas('variants', function (Builder $variants) use ($filters): void {
                $variants->where('is_active', true);
                if (isset($filters['min_price'])) {
                    $variants->where('price_amount', '>=', $filters['min_price']);
                }
                if (isset($filters['max_price'])) {
                    $variants->where('price_amount', '<=', $filters['max_price']);
                }
            });
        }

        if (isset($filters['availability'])) {
            $method = $filters['availability'] === 'available' ? 'whereExists' : 'whereNotExists';
            $query->{$method}($this->availableVariantSubquery());
        }

        $selectedOptions = array_filter([
            'color' => $filters['color'] ?? null,
            'size' => $filters['size'] ?? null,
        ]);
        if ($selectedOptions !== []) {
            $query->whereHas('variants', function (Builder $variants) use ($selectedOptions): void {
                $variants->where('is_active', true);
                foreach ($selectedOptions as $optionCode => $valueCode) {
                    $variants->whereHas('optionValues', fn (Builder $values) => $values
                        ->where('code', $valueCode)
                        ->whereHas('option', fn (Builder $option) => $option->where('code', $optionCode)));
                }
            });
        }

        if ($filters['discounted'] ?? false) {
            $query->whereHas('variants', fn (Builder $variants) => $variants
                ->where('is_active', true)
                ->whereNotNull('compare_at_amount')
                ->whereColumn('compare_at_amount', '>', 'price_amount'));
        }

        $this->applySort($query, $filters['sort'] ?? 'newest');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function detail(string $slug): ?Product
    {
        return $this->baseQuery()
            ->where('slug', $slug)
            ->with([
                'categories' => fn ($categories) => $categories->publiclyVisible()->orderBy('product_categories.sort_order'),
                'images' => fn ($images) => $images
                    ->where(function ($visibleImages): void {
                        $visibleImages->whereNull('variant_id')->orWhereHas('variant', fn ($variant) => $variant->where('is_active', true));
                    })
                    ->with(['variant' => fn ($variant) => $variant->select(['id', 'public_id'])])
                    ->orderBy('sort_order'),
                'options' => fn ($options) => $options->orderBy('sort_order'),
                'options.values' => fn ($values) => $values->orderBy('sort_order'),
                'variants' => fn ($variants) => $variants
                    ->where('is_active', true)
                    ->with([
                        'optionValues' => fn ($values) => $values->with('option')->orderBy('product_option_values.sort_order'),
                        'inventoryLevels' => fn ($levels) => $levels->whereHas('location', fn ($location) => $location->where('is_active', true)),
                    ])
                    ->orderByDesc('is_default')
                    ->orderBy('id'),
            ])
            ->first();
    }

    public function publicCategoryExists(string $slug): bool
    {
        return Category::query()->publiclyVisible()->where('slug', $slug)->exists();
    }

    /** @return array<string, array<int, array{code: string, label: string, swatch_value: ?string}>> */
    public function filterOptions(): array
    {
        return DB::table('product_option_values as values')
            ->join('product_options as options', 'options.id', '=', 'values.product_option_id')
            ->join('variant_option_values as selections', 'selections.product_option_value_id', '=', 'values.id')
            ->join('product_variants as variants', 'variants.id', '=', 'selections.variant_id')
            ->join('products', 'products.id', '=', 'options.product_id')
            ->whereIn('options.code', ['color', 'size'])
            ->where('variants.is_active', true)
            ->whereNull('variants.deleted_at')
            ->where('products.status', 'active')
            ->whereNull('products.deleted_at')
            ->whereNotNull('products.published_at')
            ->where('products.published_at', '<=', now())
            ->select(['options.code as option_code', 'values.code', 'values.label', 'values.swatch_value'])
            ->distinct()
            ->orderBy('values.label')
            ->get()
            ->groupBy('option_code')
            ->map(fn ($values) => $values->map(fn ($value) => [
                'code' => $value->code,
                'label' => $value->label,
                'swatch_value' => $value->swatch_value,
            ])->values()->all())
            ->all();
    }

    /** @return Builder<Product> */
    private function baseQuery(): Builder
    {
        return Product::query()
            ->publiclyVisible()
            ->select('products.*')
            ->selectSub($this->priceSubquery('min'), 'minimum_price_amount')
            ->selectSub($this->priceSubquery('max'), 'maximum_price_amount')
            ->selectSub($this->soldQuantitySubquery(), 'sold_quantity')
            ->selectRaw('EXISTS('.$this->availableVariantSubquery()->toSql().') as public_available', $this->availableVariantSubquery()->getBindings());
    }

    /** @return array<string, callable> */
    private function listingRelations(): array
    {
        return [
            'categories' => fn ($categories) => $categories->publiclyVisible()->orderByDesc('product_categories.is_primary')->orderBy('product_categories.sort_order'),
            'images' => fn ($images) => $images
                ->where(function ($visibleImages): void {
                    $visibleImages->whereNull('variant_id')->orWhereHas('variant', fn ($variant) => $variant->where('is_active', true));
                })
                ->orderByDesc('is_primary')
                ->orderBy('sort_order'),
            'variants' => fn ($variants) => $variants
                ->where('is_active', true)
                ->with(['optionValues.option'])
                ->orderByDesc('is_default')
                ->orderBy('id'),
        ];
    }

    /** @return Builder<ProductVariant> */
    private function priceSubquery(string $aggregate): Builder
    {
        return ProductVariant::query()
            ->selectRaw(strtoupper($aggregate).'(price_amount)')
            ->whereColumn('product_id', 'products.id')
            ->where('is_active', true);
    }

    /** @return Builder<InventoryLevel> */
    private function availableVariantSubquery(): Builder
    {
        return InventoryLevel::query()
            ->selectRaw('1')
            ->join('inventory_locations', 'inventory_locations.id', '=', 'inventory_levels.location_id')
            ->join('product_variants', 'product_variants.id', '=', 'inventory_levels.variant_id')
            ->whereColumn('product_variants.product_id', 'products.id')
            ->whereNull('product_variants.deleted_at')
            ->where('product_variants.is_active', true)
            ->where('inventory_locations.is_active', true)
            ->whereRaw('inventory_levels.on_hand > inventory_levels.reserved + inventory_levels.safety_stock');
    }

    private function soldQuantitySubquery(): QueryBuilder
    {
        return DB::table('order_items')
            ->selectRaw('COALESCE(SUM(order_items.quantity), 0)')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereColumn('order_items.product_id', 'products.id')
            ->where('orders.payment_status', 'paid')
            ->where('orders.status', '!=', 'cancelled');
    }

    /** @param Builder<Product> $query */
    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'best_selling' => $query->orderByDesc('sold_quantity')->orderByDesc('products.published_at')->orderByDesc('products.id'),
            'oldest' => $query->orderBy('products.published_at')->orderBy('products.id'),
            'price_asc' => $query->orderBy('minimum_price_amount')->orderBy('products.id'),
            'price_desc' => $query->orderByDesc('minimum_price_amount')->orderBy('products.id'),
            'name_asc' => $query->orderBy('products.name')->orderBy('products.id'),
            'name_desc' => $query->orderByDesc('products.name')->orderBy('products.id'),
            default => $query->orderByDesc('products.published_at')->orderByDesc('products.id'),
        };
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}

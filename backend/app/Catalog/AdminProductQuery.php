<?php

namespace App\Catalog;

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AdminProductQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Product::query()->with(['categories', 'images', 'variants.inventoryLevels.location'])->withCount('variants');
        if (isset($filters['search'])) {
            $query->where('name', 'like', '%'.addcslashes(trim($filters['search']), '\\%_').'%');
        }
        if (isset($filters['category'])) {
            $query->whereHas('categories', fn (Builder $q) => $q->whereRaw('LOWER(slug) = ?', [strtolower($filters['category'])]));
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['min_price'])) {
            $query->whereHas('variants', fn (Builder $q) => $q->where('price_amount', '>=', $filters['min_price']));
        }
        if (isset($filters['max_price'])) {
            $query->whereHas('variants', fn (Builder $q) => $q->where('price_amount', '<=', $filters['max_price']));
        }
        if (isset($filters['availability'])) {
            $method = $filters['availability'] === 'available' ? 'whereHas' : 'whereDoesntHave';
            $query->{$method}('variants', fn (Builder $q) => $q->where('is_active', true)->whereHas('inventoryLevels', fn (Builder $levels) => $levels->whereHas('location', fn (Builder $locations) => $locations->where('is_active', true))->whereRaw('on_hand - reserved - safety_stock > 0')));
        }
        $price = DB::table('product_variants')->selectRaw('MIN(price_amount)')->whereColumn('product_id', 'products.id')->whereNull('deleted_at');
        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            'name_asc' => $query->orderBy('name')->orderBy('id'),
            'name_desc' => $query->orderByDesc('name')->orderBy('id'),
            'price_asc' => $query->orderBy($price)->orderBy('id'),
            'price_desc' => $query->orderByDesc($price)->orderBy('id'),
            default => $query->orderByDesc('created_at')->orderByDesc('id'),
        };

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function find(string $publicId): ?Product
    {
        return Product::query()->where('public_id', $publicId)->with([
            'categories' => fn ($q) => $q->orderBy('product_categories.sort_order'),
            'images' => fn ($q) => $q->with('variant')->orderBy('sort_order'),
            'options' => fn ($q) => $q->orderBy('sort_order')->with(['values' => fn ($v) => $v->orderBy('sort_order')]),
            'variants' => fn ($q) => $q->with(['optionValues.option', 'inventoryLevels.location']),
        ])->first();
    }
}

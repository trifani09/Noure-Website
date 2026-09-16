<?php

namespace App\Catalog;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class AdminCategoryQuery
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<Category>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Category::query()
            ->with('parent')
            ->withCount(['products as direct_product_count', 'children as children_count']);

        if (isset($filters['parent'])) {
            if ($filters['parent'] === 'root') {
                $query->whereNull('parent_id');
            } else {
                $parent = $this->find($filters['parent']);
                $query->where('parent_id', $parent?->getKey() ?? -1);
            }
        }
        if (isset($filters['search'])) {
            $query->where('name', 'like', '%'.$this->escapeLike($filters['search']).'%');
        }
        if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', $filters['is_active']);
        }

        $this->applySort($query, $filters['sort'] ?? 'position');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function find(string $publicId): ?Category
    {
        return Category::query()
            ->where('public_id', $publicId)
            ->with('parent')
            ->withCount(['products as direct_product_count', 'children as children_count'])
            ->first();
    }

    /** @param Builder<Category> $query */
    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'name_asc' => $query->orderBy('name')->orderBy('id'),
            'name_desc' => $query->orderByDesc('name')->orderBy('id'),
            'newest' => $query->orderByDesc('created_at')->orderByDesc('id'),
            'oldest' => $query->orderBy('created_at')->orderBy('id'),
            default => $query->orderByRaw('parent_id IS NOT NULL')->orderBy('parent_id')->orderBy('sort_order')->orderBy('name')->orderBy('id'),
        };
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}

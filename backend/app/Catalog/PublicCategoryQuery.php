<?php

namespace App\Catalog;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class PublicCategoryQuery
{
    /**
     * @param  array{page?: int, per_page?: int, parent?: string, search?: string, include_product_count?: bool, sort?: string}  $filters
     * @return LengthAwarePaginator<Category>
     */
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = Category::query()
            ->publiclyVisible()
            ->with(['parent' => fn ($parent) => $parent->publiclyVisible()])
            ->withCount(['children as children_count' => fn ($children) => $children->publiclyVisible()]);

        if ($filters['include_product_count'] ?? false) {
            $query->withCount(['products as product_count' => fn ($products) => $products->publiclyVisible()]);
        }

        if (isset($filters['parent'])) {
            if ($filters['parent'] === 'root') {
                $query->whereNull('parent_id');
            } else {
                $parent = $this->findByPublicId($filters['parent']);
                $query->where('parent_id', $parent?->getKey() ?? -1);
            }
        }

        if (isset($filters['search'])) {
            $query->where('name', 'like', '%'.$this->escapeLike($filters['search']).'%');
        }

        $this->applySort($query, $filters['sort'] ?? 'position');

        return $query->paginate($filters['per_page'] ?? 20);
    }

    public function findByPublicId(string $publicId): ?Category
    {
        return Category::query()->publiclyVisible()->where('public_id', $publicId)->first();
    }

    public function detail(string $slug, bool $includeProductCount): ?Category
    {
        $query = Category::query()
            ->publiclyVisible()
            ->where('slug', $slug)
            ->with(['children' => fn ($children) => $children
                ->publiclyVisible()
                ->with('parent')
                ->orderBy('sort_order')
                ->orderBy('name')])
            ->withCount(['children as children_count' => fn ($children) => $children->publiclyVisible()]);

        if ($includeProductCount) {
            $query->withCount(['products as product_count' => fn ($products) => $products->publiclyVisible()]);
        }

        $category = $query->first();
        if ($category === null) {
            return null;
        }

        if ($includeProductCount && $category->children->isNotEmpty()) {
            $category->children->loadCount([
                'products as product_count' => fn ($products) => $products->publiclyVisible(),
            ]);
        }
        $category->children->loadCount([
            'children as children_count' => fn ($children) => $children->publiclyVisible(),
        ]);

        $category->setRelation('ancestors', $this->ancestors($category));

        return $category;
    }

    /** @return Collection<int, Category> */
    private function ancestors(Category $category): Collection
    {
        $ancestors = new Collection;
        $parentId = $category->parent_id;
        $categoriesById = Category::query()
            ->publiclyVisible()
            ->get(['id', 'public_id', 'parent_id', 'name', 'slug'])
            ->keyBy('id');

        while ($parentId !== null) {
            $parent = $categoriesById->get($parentId);
            if ($parent === null) {
                break;
            }

            $ancestors->prepend($parent);
            $parentId = $parent->parent_id;
        }

        return $ancestors;
    }

    /** @param Builder<Category> $query */
    private function applySort(Builder $query, string $sort): void
    {
        match ($sort) {
            'name_asc' => $query->orderBy('name')->orderBy('id'),
            'name_desc' => $query->orderByDesc('name')->orderBy('id'),
            default => $query->orderByRaw('parent_id IS NOT NULL')->orderBy('parent_id')->orderBy('sort_order')->orderBy('name')->orderBy('id'),
        };
    }

    private function escapeLike(string $value): string
    {
        return addcslashes($value, '\\%_');
    }
}

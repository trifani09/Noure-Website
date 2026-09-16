<?php

namespace App\Catalog;

use App\Exceptions\CategoryConflictException;
use App\Models\Category;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminCategoryService
{
    /** @param array<string, mixed> $attributes */
    public function create(array $attributes): Category
    {
        return DB::transaction(function () use ($attributes): Category {
            try {
                return Category::query()->create([
                    'parent_id' => $this->parentId($attributes['parent_public_id'] ?? null),
                    'name' => $attributes['name'],
                    'slug' => isset($attributes['slug']) ? $attributes['slug'] : $this->uniqueSlug($attributes['name']),
                    'description' => $attributes['description'] ?? null,
                    'image_path' => $attributes['image_path'] ?? null,
                    'sort_order' => $attributes['sort_order'] ?? 0,
                    'is_active' => $attributes['is_active'] ?? true,
                ]);
            } catch (UniqueConstraintViolationException) {
                throw new CategoryConflictException('slug_conflict', 'The category slug is already in use.');
            }
        });
    }

    /** @param array<string, mixed> $attributes */
    public function update(Category $category, array $attributes): Category
    {
        return DB::transaction(function () use ($category, $attributes): Category {
            $changes = $attributes;
            if (array_key_exists('parent_public_id', $changes)) {
                $changes['parent_id'] = $this->parentId($changes['parent_public_id']);
                unset($changes['parent_public_id']);
            }

            try {
                $category->update($changes);
            } catch (UniqueConstraintViolationException) {
                throw new CategoryConflictException('slug_conflict', 'The category slug is already in use.');
            }

            return $category;
        });
    }

    public function delete(Category $category): void
    {
        DB::transaction(function () use ($category): void {
            if ($category->children()->exists()) {
                throw new CategoryConflictException('category_has_children', 'Move or delete child categories before deleting this category.');
            }
            if (DB::table('product_categories')->where('category_id', $category->getKey())->exists()) {
                throw new CategoryConflictException('category_has_products', 'Detach products before deleting this category.');
            }

            $category->delete();
        });
    }

    private function parentId(?string $publicId): ?int
    {
        if ($publicId === null) {
            return null;
        }

        return Category::query()->where('public_id', $publicId)->value('id');
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'category';
        $base = Str::limit($base, 180, '');
        $slug = $base;
        $suffix = 2;

        while (Category::query()->where('slug', $slug)->exists()) {
            $ending = '-'.$suffix++;
            $slug = Str::limit($base, 180 - strlen($ending), '').$ending;
        }

        return $slug;
    }
}

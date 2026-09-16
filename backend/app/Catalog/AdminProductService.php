<?php

namespace App\Catalog;

use App\Exceptions\ProductConflictException;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminProductService
{
    public function create(array $attributes): Product
    {
        return DB::transaction(function () use ($attributes): Product {
            try {
                $product = Product::query()->create($this->scalars($attributes, true));
                $this->replaceCategories($product, $attributes['categories']);
                $valueMap = $this->replaceOptions($product, $attributes['options']);
                $variants = $this->replaceVariants($product, $attributes['variants'], $valueMap);
                $this->replaceImages($product, $attributes['images'], $variants);

                return $product;
            } catch (UniqueConstraintViolationException $exception) {
                throw $this->conflictFor($exception);
            }
        });
    }

    public function update(Product $product, array $attributes): Product
    {
        return DB::transaction(function () use ($product, $attributes): Product {
            try {
                $product->update($this->scalars($attributes, false));
                if (array_key_exists('categories', $attributes)) {
                    $this->replaceCategories($product, $attributes['categories']);
                }
                $valueMap = array_key_exists('options', $attributes) ? $this->replaceOptions($product, $attributes['options']) : $this->existingValueMap($product);
                $variants = array_key_exists('variants', $attributes) ? $this->replaceVariants($product, $attributes['variants'], $valueMap) : $product->variants()->get()->keyBy('sku');
                if (array_key_exists('images', $attributes)) {
                    $this->replaceImages($product, $attributes['images'], $variants);
                }

                return $product;
            } catch (UniqueConstraintViolationException $exception) {
                throw $this->conflictFor($exception);
            }
        });
    }

    public function delete(Product $product): void
    {
        DB::transaction(function () use ($product): void {
            $product->variants()->delete();
            $product->delete();
        });
    }

    private function scalars(array $attributes, bool $creating): array
    {
        $keys = ['name', 'slug', 'short_description', 'description', 'brand', 'status', 'published_at', 'metadata'];
        $data = array_intersect_key($attributes, array_flip($keys));
        if ($creating && ! isset($data['slug'])) {
            $data['slug'] = $this->uniqueSlug($attributes['name']);
        }

        return $data;
    }

    private function replaceCategories(Product $product, array $categories): void
    {
        $ids = Category::query()->whereIn('public_id', array_column($categories, 'category_public_id'))->pluck('id', 'public_id');
        $sync = [];
        foreach ($categories as $category) {
            $sync[$ids[$category['category_public_id']]] = ['is_primary' => $category['is_primary'], 'sort_order' => $category['sort_order']];
        }
        $product->categories()->sync($sync);
    }

    private function replaceOptions(Product $product, array $options): array
    {
        $product->options()->delete();
        $map = [];
        foreach ($options as $optionData) {
            $values = $optionData['values'];
            unset($optionData['values']);
            $option = $product->options()->create($optionData);
            foreach ($values as $valueData) {
                $map[$option->code][$valueData['code']] = $option->values()->create($valueData)->id;
            }
        }

        return $map;
    }

    private function existingValueMap(Product $product): array
    {
        $map = [];
        foreach ($product->options()->with('values')->get() as $option) {
            foreach ($option->values as $value) {
                $map[$option->code][$value->code] = $value->id;
            }
        }

        return $map;
    }

    private function replaceVariants(Product $product, array $variants, array $valueMap)
    {
        $retained = [];
        $bySku = collect();
        foreach ($variants as $variantData) {
            $publicId = $variantData['public_id'] ?? null;
            $selected = $variantData['option_values'];
            unset($variantData['option_values'], $variantData['public_id']);
            $valueIds = [];
            foreach ($selected as $option => $value) {
                if (! isset($valueMap[$option][$value])) {
                    throw new ProductConflictException('cross_product_option_values', 'A variant contains an option value outside this product.');
                }
                $valueIds[] = $valueMap[$option][$value];
            }
            $variantData['combination_key'] = implode(':', $valueIds);
            if ($publicId) {
                $variant = $product->variants()->where('public_id', $publicId)->first();
                if (! $variant) {
                    throw new ProductConflictException('cross_product_option_values', 'A retained variant does not belong to this product.');
                }
                $variant->update($variantData);
            } else {
                $variant = $product->variants()->create($variantData);
            }
            $variant->optionValues()->sync($valueIds);
            $retained[] = $variant->id;
            $bySku->put($variant->sku, $variant);
        }
        $product->variants()->whereNotIn('id', $retained)->delete();

        return $bySku;
    }

    private function replaceImages(Product $product, array $images, $variants): void
    {
        $product->images()->delete();
        foreach ($images as $image) {
            $sku = $image['variant_sku'] ?? null;
            unset($image['variant_sku']);
            if ($sku && ! $variants->has($sku)) {
                throw new ProductConflictException('variant_not_found', 'An image references an unknown variant SKU.');
            }
            $image['variant_id'] = $sku ? $variants->get($sku)->id : null;
            $product->images()->create($image);
        }
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::limit(Str::slug($name) ?: 'product', 220, '');
        $slug = $base;
        $suffix = 2;
        while (Product::query()->where('slug', $slug)->exists()) {
            $ending = '-'.$suffix++;
            $slug = Str::limit($base, 220 - strlen($ending), '').$ending;
        }

        return $slug;
    }

    private function conflictFor(UniqueConstraintViolationException $exception): ProductConflictException
    {
        $message = strtolower($exception->getMessage());
        if (str_contains($message, 'sku')) {
            return new ProductConflictException('sku_conflict', 'The SKU is already in use.');
        }
        if (str_contains($message, 'combination')) {
            return new ProductConflictException('variant_combination_conflict', 'Variant option combinations must be unique.');
        }

        return new ProductConflictException('product_conflict', 'A product value is already in use.');
    }
}

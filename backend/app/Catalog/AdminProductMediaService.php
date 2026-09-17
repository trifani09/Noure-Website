<?php

namespace App\Catalog;

use App\Exceptions\ProductConflictException;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class AdminProductMediaService
{
    public function list(Product $product)
    {
        return $product->images()->with('variant')->orderBy('sort_order')->orderBy('id')->get();
    }

    public function upload(Product $product, UploadedFile $file, array $attributes): ProductImage
    {
        $variant = $this->variant($product, $attributes['variant_public_id'] ?? null);
        $path = $file->store('products/'.$product->slug, 'public');
        if (! $path) {
            throw new ProductConflictException('media_storage_failed', 'The image could not be stored.');
        }
        try {
            return DB::transaction(function () use ($product, $file, $attributes, $variant, $path): ProductImage {
                Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
                $scope = $product->images()->where('variant_id', $variant?->id);
                $isPrimary = ($attributes['is_primary'] ?? false) || ! (clone $scope)->exists();
                if ($isPrimary) {
                    (clone $scope)->update(['is_primary' => false]);
                }
                $image = $product->images()->create([
                    'variant_id' => $variant?->id,
                    'path' => $path,
                    'alt_text' => $attributes['alt_text'] ?? null,
                    'width' => $this->dimensions($file)[0],
                    'height' => $this->dimensions($file)[1],
                    'mime_type' => $file->getMimeType(),
                    'sort_order' => $attributes['sort_order'] ?? ((int) (clone $scope)->max('sort_order') + 1),
                    'is_primary' => $isPrimary,
                ]);

                return $image->load('variant');
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete($path);
            throw $exception;
        }
    }

    public function update(Product $product, ProductImage $image, array $attributes): ProductImage
    {
        return DB::transaction(function () use ($product, $image, $attributes): ProductImage {
            Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $oldVariantId = $image->variant_id;
            $variant = array_key_exists('variant_public_id', $attributes) ? $this->variant($product, $attributes['variant_public_id']) : $image->variant;
            $newVariantId = $variant?->id;
            $wasPrimary = $image->is_primary;
            $becomesPrimary = $attributes['is_primary'] ?? ($wasPrimary && $oldVariantId === $newVariantId);
            if ($becomesPrimary) {
                $product->images()->where('variant_id', $newVariantId)->whereKeyNot($image->id)->update(['is_primary' => false]);
            }
            $image->update(array_filter([
                'variant_id' => array_key_exists('variant_public_id', $attributes) ? $newVariantId : null,
                'alt_text' => $attributes['alt_text'] ?? null,
                'sort_order' => $attributes['sort_order'] ?? null,
                'is_primary' => array_key_exists('is_primary', $attributes) || $oldVariantId !== $newVariantId ? $becomesPrimary : null,
            ], fn ($value, $key) => $value !== null || (in_array($key, ['alt_text', 'variant_id'], true) && array_key_exists($key === 'variant_id' ? 'variant_public_id' : $key, $attributes)), ARRAY_FILTER_USE_BOTH));
            if ($wasPrimary && ($oldVariantId !== $newVariantId || ! $becomesPrimary)) {
                $this->promoteFirst($product, $oldVariantId, $image->id);
            }
            if (! $product->images()->where('variant_id', $newVariantId)->where('is_primary', true)->exists()) {
                $image->update(['is_primary' => true]);
            }

            return $image->load('variant');
        });
    }

    public function delete(Product $product, ProductImage $image): void
    {
        $path = $image->path;
        DB::transaction(function () use ($product, $image): void {
            Product::query()->whereKey($product->id)->lockForUpdate()->firstOrFail();
            $variantId = $image->variant_id;
            $wasPrimary = $image->is_primary;
            $image->delete();
            if ($wasPrimary) {
                $this->promoteFirst($product, $variantId);
            }
        });
        Storage::disk('public')->delete($path);
    }

    private function variant(Product $product, ?string $publicId): ?ProductVariant
    {
        if ($publicId === null) {
            return null;
        }
        $variant = $product->variants()->where('public_id', $publicId)->first();
        if (! $variant) {
            throw new ProductConflictException('variant_not_found', 'The selected variant does not belong to this product.');
        }

        return $variant;
    }

    private function promoteFirst(Product $product, ?int $variantId, ?int $excludedId = null): void
    {
        $query = $product->images()->where('variant_id', $variantId)->orderBy('sort_order')->orderBy('id');
        if ($excludedId !== null) {
            $query->whereKeyNot($excludedId);
        }
        $query->first()?->update(['is_primary' => true]);
    }

    private function dimensions(UploadedFile $file): array
    {
        $dimensions = @getimagesize($file->getRealPath());

        return $dimensions ? [$dimensions[0], $dimensions[1]] : [null, null];
    }
}

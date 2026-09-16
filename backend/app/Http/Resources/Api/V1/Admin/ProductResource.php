<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $detail = $this->relationLoaded('options');
        $primaryCategory = $this->categories->first(fn ($category) => (bool) $category->pivot->is_primary);
        $primaryImage = $this->images->first(fn ($image) => $image->variant_id === null && $image->is_primary) ?? $this->images->first();
        $variants = $this->variants;
        $lowest = $variants->min('price_amount');
        $available = $variants->contains(fn ($variant) => $this->available($variant));
        $base = [
            'public_id' => $this->public_id, 'name' => $this->name, 'slug' => $this->slug,
            'short_description' => $this->short_description, 'brand' => $this->brand, 'status' => $this->status,
            'primary_image' => $primaryImage ? ['url' => $this->url($primaryImage->path), 'alt_text' => $primaryImage->alt_text] : null,
            'primary_category' => $primaryCategory ? ['public_id' => $primaryCategory->public_id, 'name' => $primaryCategory->name, 'slug' => $primaryCategory->slug] : null,
            'variant_count' => $variants->count(), 'lowest_price' => $lowest, 'currency' => $variants->first()?->currency,
            'available' => $available, 'published_at' => $this->published_at?->utc()->toISOString(),
            'created_at' => $this->created_at?->utc()->toISOString(), 'updated_at' => $this->updated_at?->utc()->toISOString(),
        ];
        if (! $detail) {
            return $base;
        }

        return array_merge($base, [
            'description' => $this->description, 'metadata' => $this->metadata,
            'categories' => $this->categories->map(fn ($category) => ['public_id' => $category->public_id, 'name' => $category->name, 'slug' => $category->slug, 'is_primary' => (bool) $category->pivot->is_primary, 'sort_order' => (int) $category->pivot->sort_order])->values(),
            'images' => $this->images->map(fn ($image) => ['path' => $image->path, 'url' => $this->url($image->path), 'alt_text' => $image->alt_text, 'width' => $image->width, 'height' => $image->height, 'mime_type' => $image->mime_type, 'sort_order' => $image->sort_order, 'is_primary' => $image->is_primary, 'variant_public_id' => $image->variant?->public_id])->values(),
            'options' => $this->options->map(fn ($option) => ['name' => $option->name, 'code' => $option->code, 'sort_order' => $option->sort_order, 'values' => $option->values->map(fn ($value) => ['label' => $value->label, 'code' => $value->code, 'swatch_value' => $value->swatch_value, 'sort_order' => $value->sort_order])->values()])->values(),
            'variants' => $variants->map(fn ($variant) => ['public_id' => $variant->public_id, 'sku' => $variant->sku, 'title' => $variant->title, 'option_values' => $variant->optionValues->mapWithKeys(fn ($value) => [$value->option->code => $value->code]), 'price_amount' => $variant->price_amount, 'compare_at_amount' => $variant->compare_at_amount, 'currency' => $variant->currency, 'barcode' => $variant->barcode, 'weight_grams' => $variant->weight_grams, 'is_active' => $variant->is_active, 'is_default' => $variant->is_default, 'available' => $this->available($variant), 'created_at' => $variant->created_at?->utc()->toISOString(), 'updated_at' => $variant->updated_at?->utc()->toISOString()])->values(),
        ]);
    }

    private function available($variant): bool
    {
        return $variant->is_active && $variant->relationLoaded('inventoryLevels') && $variant->inventoryLevels->contains(fn ($level) => $level->location?->is_active && $level->on_hand - $level->reserved - $level->safety_stock > 0);
    }

    private function url(string $path): string
    {
        return Str::startsWith($path, ['http://', 'https://']) ? $path : Storage::disk('public')->url($path);
    }
}

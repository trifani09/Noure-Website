<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicProductDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'brand' => $this->brand,
            'material' => $this->metadata['material'] ?? null,
            'care_instructions' => $this->metadata['care_instructions'] ?? null,
            'shipping_information' => $this->metadata['shipping_information'] ?? null,
            'categories' => $this->categories->map(fn ($category) => [
                'public_id' => $category->public_id,
                'name' => $category->name,
                'slug' => $category->slug,
                'is_primary' => (bool) $category->pivot->is_primary,
            ])->values(),
            'images' => $this->images->map(fn ($image) => [
                'url' => $this->imageUrl($image->path),
                'alt_text' => $image->alt_text,
                'width' => $image->width,
                'height' => $image->height,
                'mime_type' => $image->mime_type,
                'sort_order' => $image->sort_order,
                'is_primary' => (bool) $image->is_primary,
                'variant_public_id' => $image->variant?->public_id,
            ])->values(),
            'options' => $this->options->map(fn ($option) => [
                'name' => $option->name,
                'code' => $option->code,
                'sort_order' => $option->sort_order,
                'values' => $option->values->map(fn ($value) => [
                    'label' => $value->label,
                    'code' => $value->code,
                    'swatch_value' => $value->swatch_value,
                    'sort_order' => $value->sort_order,
                ])->values(),
            ])->values(),
            'variants' => $this->variants->map(fn ($variant) => [
                'public_id' => $variant->public_id,
                'sku' => $variant->sku,
                'title' => $variant->title,
                'selected_options' => $variant->optionValues
                    ->sortBy(fn ($value) => [$value->option->sort_order, $value->sort_order])
                    ->map(fn ($value) => [
                        'option_code' => $value->option->code,
                        'option_name' => $value->option->name,
                        'value_code' => $value->code,
                        'value_label' => $value->label,
                    ])->values(),
                'price_amount' => $variant->price_amount,
                'compare_at_amount' => $variant->compare_at_amount,
                'currency' => $variant->currency,
                'available' => $this->variantAvailable($variant),
                'inventory_status' => $this->variantInventoryStatus($variant),
                'is_default' => (bool) $variant->is_default,
            ])->values(),
            'available' => (bool) $this->public_available,
            'published_at' => $this->published_at?->utc()->toISOString(),
            'created_at' => $this->created_at?->utc()->toISOString(),
            'updated_at' => $this->updated_at?->utc()->toISOString(),
        ];
    }

    private function variantAvailable(object $variant): bool
    {
        return $this->availableQuantity($variant) > 0;
    }

    private function variantInventoryStatus(object $variant): string
    {
        $available = $this->availableQuantity($variant);

        return match (true) {
            $available === 0 => 'out_of_stock',
            $available <= 3 => 'low_stock',
            default => 'in_stock',
        };
    }

    private function availableQuantity(object $variant): int
    {
        return (int) $variant->inventoryLevels->sum(
            fn ($level) => max($level->on_hand - $level->reserved - $level->safety_stock, 0)
        );
    }

    private function imageUrl(string $path): string
    {
        return Str::startsWith($path, ['http://', 'https://']) ? $path : Storage::disk('public')->url($path);
    }
}

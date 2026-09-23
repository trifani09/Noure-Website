<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicProductListResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $primaryImage = $this->images->firstWhere('is_primary', true) ?? $this->images->first();
        $secondaryImage = $this->images->first(fn ($image) => $primaryImage === null || $image->id !== $primaryImage->id);
        $primaryCategory = $this->categories->first(fn ($category) => (bool) $category->pivot->is_primary)
            ?? $this->categories->first();
        $defaultVariant = $this->variants->firstWhere('is_default', true)
            ?? $this->variants->sortBy('price_amount')->first();
        $colors = $this->variants
            ->flatMap->optionValues
            ->filter(fn ($value) => $value->option?->code === 'color')
            ->unique('code')
            ->sortBy('sort_order')
            ->map(fn ($value) => [
                'code' => $value->code,
                'label' => $value->label,
                'swatch_value' => $value->swatch_value,
            ])->values();

        return [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'short_description' => $this->short_description,
            'primary_image' => $primaryImage === null ? null : [
                'url' => $this->imageUrl($primaryImage->path),
                'alt_text' => $primaryImage->alt_text,
                'width' => $primaryImage->width,
                'height' => $primaryImage->height,
            ],
            'secondary_image' => $secondaryImage === null ? null : [
                'url' => $this->imageUrl($secondaryImage->path),
                'alt_text' => $secondaryImage->alt_text,
                'width' => $secondaryImage->width,
                'height' => $secondaryImage->height,
            ],
            'primary_category' => $primaryCategory === null ? null : [
                'public_id' => $primaryCategory->public_id,
                'name' => $primaryCategory->name,
                'slug' => $primaryCategory->slug,
            ],
            'price' => [
                'price_amount' => $defaultVariant->price_amount,
                'compare_at_amount' => $defaultVariant->compare_at_amount,
                'currency' => $defaultVariant->currency,
            ],
            'price_range' => [
                'min_price_amount' => (int) $this->minimum_price_amount,
                'max_price_amount' => (int) $this->maximum_price_amount,
                'currency' => $defaultVariant->currency,
            ],
            'available' => (bool) $this->public_available,
            'is_new' => $this->published_at?->greaterThanOrEqualTo(now()->subDays(30)) ?? false,
            'is_best_seller' => (int) $this->sold_quantity > 0,
            'colors' => $colors,
            'quick_add_variant_id' => $this->variants->count() === 1 && (bool) $this->public_available
                ? $this->variants->first()->public_id
                : null,
            'published_at' => $this->published_at?->utc()->toISOString(),
        ];
    }

    private function imageUrl(string $path): string
    {
        return Str::startsWith($path, ['http://', 'https://']) ? $path : Storage::disk('public')->url($path);
    }
}

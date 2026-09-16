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
        $primaryCategory = $this->categories->first(fn ($category) => (bool) $category->pivot->is_primary)
            ?? $this->categories->first();
        $defaultVariant = $this->variants->firstWhere('is_default', true)
            ?? $this->variants->sortBy('price_amount')->first();

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
            'published_at' => $this->published_at?->utc()->toISOString(),
        ];
    }

    private function imageUrl(string $path): string
    {
        return Str::startsWith($path, ['http://', 'https://']) ? $path : Storage::disk('public')->url($path);
    }
}

<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PublicCategoryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = [
            'public_id' => $this->public_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_url' => $this->imageUrl($this->image_path),
            'parent' => $this->whenLoaded('parent', fn () => $this->parent === null ? null : $this->summary($this->parent)),
        ];

        if (array_key_exists('product_count', $this->getAttributes())) {
            $data['product_count'] = (int) $this->product_count;
        }

        if (array_key_exists('children_count', $this->getAttributes())) {
            $data['children_count'] = (int) $this->children_count;
        }

        if ($this->relationLoaded('ancestors')) {
            $data['ancestors'] = $this->ancestors->map(fn ($category) => $this->summary($category))->values();
        }

        if ($this->relationLoaded('children')) {
            $data['children'] = self::collection($this->children)->resolve($request);
        }

        return $data;
    }

    /** @return array{public_id: string, name: string, slug: string} */
    private function summary(object $category): array
    {
        return [
            'public_id' => $category->public_id,
            'name' => $category->name,
            'slug' => $category->slug,
        ];
    }

    private function imageUrl(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        return Str::startsWith($path, ['http://', 'https://']) ? $path : Storage::disk('public')->url($path);
    }
}

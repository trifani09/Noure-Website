<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryResource extends JsonResource
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
            'parent' => $this->parent === null ? null : [
                'public_id' => $this->parent->public_id,
                'name' => $this->parent->name,
                'slug' => $this->parent->slug,
            ],
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image_path' => $this->image_path,
            'image_url' => $this->imageUrl(),
            'sort_order' => $this->sort_order,
            'is_active' => (bool) $this->is_active,
            'direct_product_count' => (int) $this->direct_product_count,
            'children_count' => (int) $this->children_count,
            'created_at' => $this->created_at?->utc()->toISOString(),
            'updated_at' => $this->updated_at?->utc()->toISOString(),
        ];
    }

    private function imageUrl(): ?string
    {
        if ($this->image_path === null) {
            return null;
        }

        return Str::startsWith($this->image_path, ['http://', 'https://'])
            ? $this->image_path
            : Storage::disk('public')->url($this->image_path);
    }
}

<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomepageSectionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['public_id' => $this->public_id, 'name' => $this->name, 'type' => $this->type, 'is_active' => $this->is_active,
            'sort_order' => $this->sort_order, 'configuration' => $this->configuration ?? [],
            'categories' => $this->categories->map(fn ($category) => ['public_id' => $category->public_id, 'name' => $category->name, 'slug' => $category->slug])->values(),
            'products' => $this->products->map(fn ($product) => ['public_id' => $product->public_id, 'name' => $product->name, 'slug' => $product->slug])->values()];
    }
}

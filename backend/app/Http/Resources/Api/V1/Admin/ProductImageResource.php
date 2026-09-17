<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductImageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'url' => Storage::disk('public')->url($this->path),
            'alt_text' => $this->alt_text,
            'width' => $this->width,
            'height' => $this->height,
            'mime_type' => $this->mime_type,
            'sort_order' => $this->sort_order,
            'is_primary' => $this->is_primary,
            'variant_public_id' => $this->variant?->public_id,
        ];
    }
}

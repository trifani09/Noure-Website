<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class BannerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return ['public_id' => $this->public_id, 'name' => $this->name, 'placement' => $this->placement, 'headline' => $this->headline,
            'subheading' => $this->subheading, 'cta_label' => $this->cta_label, 'cta_url' => $this->cta_url,
            'desktop_image_url' => Storage::disk('public')->url($this->desktop_image_path),
            'mobile_image_url' => $this->mobile_image_path ? Storage::disk('public')->url($this->mobile_image_path) : null,
            'alt_text' => $this->alt_text, 'sort_order' => $this->sort_order, 'is_active' => $this->is_active,
            'starts_at' => $this->starts_at?->utc()->toISOString(), 'ends_at' => $this->ends_at?->utc()->toISOString()];
    }
}

<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryLevelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'variant_public_id' => $this->variant->public_id,
            'location' => ['code' => $this->location->code, 'name' => $this->location->name, 'is_active' => $this->location->is_active],
            'on_hand' => $this->on_hand,
            'reserved' => $this->reserved,
            'safety_stock' => $this->safety_stock,
            'available' => $this->available,
            'version' => $this->version,
            'updated_at' => $this->updated_at?->utc()->toISOString(),
        ];
    }
}

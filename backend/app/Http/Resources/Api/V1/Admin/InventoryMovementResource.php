<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'variant_public_id' => $this->inventoryLevel->variant->public_id,
            'location' => ['code' => $this->inventoryLevel->location->code, 'name' => $this->inventoryLevel->location->name],
            'quantity_delta' => $this->quantity_delta,
            'movement_type' => $this->movement_type,
            'reference_type' => $this->reference_type,
            'reference_id' => $this->reference_id,
            'reason' => $this->reason,
            'actor' => $this->actor ? ['name' => $this->actor->name, 'email' => $this->actor->email] : null,
            'created_at' => $this->created_at?->utc()->toISOString(),
        ];
    }
}

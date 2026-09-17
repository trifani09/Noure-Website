<?php

namespace App\Catalog;

use App\Exceptions\InventoryConflictException;
use App\Models\InventoryLevel;
use App\Models\InventoryLocation;
use App\Models\InventoryMovement;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class AdminInventoryService
{
    public function levels(ProductVariant $variant)
    {
        return $variant->inventoryLevels()->with(['variant', 'location'])->orderBy(
            InventoryLocation::query()->select('code')->whereColumn('inventory_locations.id', 'inventory_levels.location_id')
        )->get();
    }

    public function adjust(ProductVariant $variant, User $actor, array $attributes): array
    {
        return DB::transaction(function () use ($variant, $actor, $attributes): array {
            $location = InventoryLocation::query()->where('code', $attributes['location_code'])->where('is_active', true)->first();
            if (! $location) {
                throw new InventoryConflictException('location_not_found', 'The requested active inventory location was not found.');
            }

            $level = InventoryLevel::query()->where('variant_id', $variant->id)->where('location_id', $location->id)->lockForUpdate()->first();
            if (! $level) {
                if ($attributes['expected_version'] !== 0) {
                    throw new InventoryConflictException('inventory_version_conflict', 'The inventory level has changed. Refresh and try again.');
                }
                $level = InventoryLevel::query()->create(['variant_id' => $variant->id, 'location_id' => $location->id, 'on_hand' => 0, 'reserved' => 0, 'safety_stock' => 0, 'version' => 0]);
            }
            if ($level->version !== $attributes['expected_version']) {
                throw new InventoryConflictException('inventory_version_conflict', 'The inventory level has changed. Refresh and try again.');
            }
            $newOnHand = $level->on_hand + $attributes['quantity_delta'];
            if ($newOnHand < 0 || $newOnHand - $level->reserved - $level->safety_stock < 0) {
                throw new InventoryConflictException('insufficient_stock', 'The adjustment would make physical or available stock negative.');
            }
            $level->update(['on_hand' => $newOnHand, 'version' => $level->version + 1]);
            $movement = $level->movements()->create([
                'quantity_delta' => $attributes['quantity_delta'],
                'movement_type' => 'adjustment',
                'reference_type' => $attributes['reference_type'] ?? null,
                'reference_id' => $attributes['reference_id'] ?? null,
                'reason' => $attributes['reason'],
                'actor_id' => $actor->id,
            ]);

            return [$level->load(['variant', 'location']), $movement->load(['inventoryLevel.variant', 'inventoryLevel.location', 'actor'])];
        });
    }

    public function movements(ProductVariant $variant, array $filters): LengthAwarePaginator
    {
        $query = InventoryMovement::query()->whereHas('inventoryLevel', fn ($level) => $level->where('variant_id', $variant->id))
            ->with(['inventoryLevel.variant', 'inventoryLevel.location', 'actor']);
        if (isset($filters['location_code'])) {
            $query->whereHas('inventoryLevel.location', fn ($location) => $location->where('code', $filters['location_code']));
        }
        if (isset($filters['movement_type'])) {
            $query->where('movement_type', $filters['movement_type']);
        }
        if (isset($filters['from'])) {
            $query->where('inventory_movements.created_at', '>=', $filters['from']);
        }
        if (isset($filters['to'])) {
            $query->where('inventory_movements.created_at', '<=', $filters['to']);
        }
        $direction = ($filters['sort'] ?? 'newest') === 'oldest' ? 'asc' : 'desc';

        return $query->orderBy('inventory_movements.created_at', $direction)->orderBy('inventory_movements.id', $direction)->paginate($filters['per_page'] ?? 20);
    }
}

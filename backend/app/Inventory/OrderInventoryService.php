<?php

namespace App\Inventory;

use App\Models\InventoryLevel;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OrderInventoryService
{
    public function convertReservationsToSale(Order $order): void
    {
        DB::transaction(function () use ($order): void {
            $groups = $this->reservations($order);
            foreach ($groups as $levelId => $reservations) {
                $reservedQuantity = abs($reservations->sum('quantity_delta'));
                $soldQuantity = abs($this->movements($order, (int) $levelId, 'sale')->sum('quantity_delta'));
                $releasedQuantity = $this->movements($order, (int) $levelId, 'release')->sum('quantity_delta');
                if ($soldQuantity === $reservedQuantity) {
                    continue;
                }
                if ($releasedQuantity > 0 || $soldQuantity > 0) {
                    throw new InventoryLifecycleException('inventory_lifecycle_conflict', 'The reservation is partially processed and requires manual reconciliation.');
                }
                $level = InventoryLevel::query()->whereKey($levelId)->lockForUpdate()->first();
                if (! $level || $level->reserved < $reservedQuantity || $level->on_hand < $reservedQuantity) {
                    throw new InventoryLifecycleException('reservation_mismatch', 'Reserved inventory cannot be converted safely.');
                }
                $level->update([
                    'on_hand' => $level->on_hand - $reservedQuantity,
                    'reserved' => $level->reserved - $reservedQuantity,
                    'version' => $level->version + 1,
                ]);
                InventoryMovement::query()->create([
                    'inventory_level_id' => $level->id, 'quantity_delta' => -$reservedQuantity,
                    'movement_type' => 'sale', 'reference_type' => 'order', 'reference_id' => $order->public_id,
                    'reason' => 'Reservation converted to sale for '.$order->order_number,
                ]);
            }
        });
    }

    public function releaseReservations(Order $order, ?User $actor = null, string $reason = 'Order reservation released'): void
    {
        DB::transaction(function () use ($order, $actor, $reason): void {
            foreach ($this->reservations($order) as $levelId => $reservations) {
                $reservedQuantity = abs($reservations->sum('quantity_delta'));
                $soldQuantity = abs($this->movements($order, (int) $levelId, 'sale')->sum('quantity_delta'));
                if ($soldQuantity > 0) {
                    throw new InventoryLifecycleException('inventory_already_sold', 'Sold inventory cannot be restored without a return workflow.');
                }
                $releasedQuantity = $this->movements($order, (int) $levelId, 'release')->sum('quantity_delta');
                $quantity = $reservedQuantity - $releasedQuantity;
                if ($quantity <= 0) {
                    continue;
                }
                $level = InventoryLevel::query()->whereKey($levelId)->lockForUpdate()->first();
                if (! $level || $level->reserved < $quantity) {
                    throw new InventoryLifecycleException('reservation_mismatch', 'Reserved inventory cannot be released safely.');
                }
                $level->update(['reserved' => $level->reserved - $quantity, 'version' => $level->version + 1]);
                InventoryMovement::query()->create([
                    'inventory_level_id' => $level->id, 'quantity_delta' => $quantity,
                    'movement_type' => 'release', 'reference_type' => 'order', 'reference_id' => $order->public_id,
                    'reason' => $reason.' '.$order->order_number, 'actor_id' => $actor?->id,
                ]);
            }
        });
    }

    /** @return array{reserved: int, sold: int, released: int, state: string} */
    public function state(Order $order): array
    {
        $reserved = abs(InventoryMovement::query()->where('reference_type', 'order')->where('reference_id', $order->public_id)->where('movement_type', 'reservation')->sum('quantity_delta'));
        $sold = abs(InventoryMovement::query()->where('reference_type', 'order')->where('reference_id', $order->public_id)->where('movement_type', 'sale')->sum('quantity_delta'));
        $released = InventoryMovement::query()->where('reference_type', 'order')->where('reference_id', $order->public_id)->where('movement_type', 'release')->sum('quantity_delta');
        $state = $sold >= $reserved && $reserved > 0 ? 'sold' : ($released >= $reserved && $reserved > 0 ? 'released' : ($reserved > 0 ? 'reserved' : 'none'));

        return compact('reserved', 'sold', 'released', 'state');
    }

    /** @return Collection<int, Collection<int, InventoryMovement>> */
    private function reservations(Order $order): Collection
    {
        return InventoryMovement::query()->where('reference_type', 'order')->where('reference_id', $order->public_id)
            ->where('movement_type', 'reservation')->lockForUpdate()->get()->groupBy('inventory_level_id');
    }

    /** @return Collection<int, InventoryMovement> */
    private function movements(Order $order, int $levelId, string $type): Collection
    {
        return InventoryMovement::query()->where('reference_type', 'order')->where('reference_id', $order->public_id)
            ->where('inventory_level_id', $levelId)->where('movement_type', $type)->lockForUpdate()->get();
    }
}

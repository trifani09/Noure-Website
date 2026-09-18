<?php

namespace App\Orders;

use App\Models\InventoryLevel;
use App\Models\InventoryMovement;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminOrderService
{
    /** @var array<string, array<int, string>> */
    private const TRANSITIONS = [
        'pending' => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped' => ['completed'],
        'completed' => [],
        'cancelled' => [],
    ];

    public function updateStatus(Order $order, string $status, User $actor): Order
    {
        return DB::transaction(function () use ($order, $status, $actor): Order {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if (! in_array($status, self::TRANSITIONS[$order->status] ?? [], true)) {
                throw new OrderConflictException('invalid_status_transition', "Order status cannot move from {$order->status} to {$status}.");
            }
            if ($status === 'cancelled') {
                $this->releaseReservations($order, $actor);
            }
            $metadata = $order->metadata ?? [];
            $history = $metadata['status_history'] ?? [];
            $history[] = [
                'from' => $order->status,
                'to' => $status,
                'changed_at' => now()->utc()->toISOString(),
                'actor' => ['id' => $actor->id, 'name' => $actor->name, 'email' => $actor->email],
            ];
            $metadata['status_history'] = $history;
            $order->update([
                'status' => $status,
                'cancelled_at' => $status === 'cancelled' ? now() : $order->cancelled_at,
                'metadata' => $metadata,
            ]);

            return $order->fresh(['customer', 'items', 'payments']);
        });
    }

    private function releaseReservations(Order $order, User $actor): void
    {
        $reservations = InventoryMovement::query()
            ->where('reference_type', 'order')->where('reference_id', $order->public_id)
            ->where('movement_type', 'reservation')->lockForUpdate()->get()->groupBy('inventory_level_id');
        $releasedLevelIds = InventoryMovement::query()
            ->where('reference_type', 'order')->where('reference_id', $order->public_id)
            ->where('movement_type', 'release')->pluck('inventory_level_id');
        foreach ($reservations as $levelId => $movements) {
            if ($releasedLevelIds->contains($levelId)) {
                continue;
            }
            $quantity = abs($movements->sum('quantity_delta'));
            $level = InventoryLevel::query()->whereKey($levelId)->lockForUpdate()->first();
            if (! $level || $quantity === 0) {
                continue;
            }
            if ($level->reserved < $quantity) {
                throw new OrderConflictException('reservation_mismatch', 'Reserved inventory could not be released safely.');
            }
            $level->update(['reserved' => $level->reserved - $quantity, 'version' => $level->version + 1]);
            InventoryMovement::query()->create([
                'inventory_level_id' => $level->id,
                'quantity_delta' => $quantity,
                'movement_type' => 'release',
                'reference_type' => 'order',
                'reference_id' => $order->public_id,
                'reason' => 'Released after order cancellation '.$order->order_number,
                'actor_id' => $actor->id,
            ]);
        }
    }
}

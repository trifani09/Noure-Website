<?php

namespace App\Orders;

use App\Inventory\InventoryLifecycleException;
use App\Inventory\OrderInventoryService;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminOrderService
{
    public function __construct(private readonly OrderInventoryService $inventory) {}

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
            if ($status === 'shipped' && ! in_array($order->fulfillment_status, ['shipped', 'fulfilled'], true)) {
                throw new OrderConflictException('fulfillment_required', 'Use the fulfillment workflow before marking an order shipped.');
            }
            if ($status === 'completed' && $order->fulfillment_status !== 'fulfilled') {
                throw new OrderConflictException('fulfillment_required', 'The order must be fulfilled before it can be completed.');
            }
            if ($status === 'cancelled') {
                try {
                    $this->inventory->releaseReservations($order, $actor, 'Released after order cancellation');
                } catch (InventoryLifecycleException $exception) {
                    throw new OrderConflictException($exception->errorCode, $exception->getMessage());
                }
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
}

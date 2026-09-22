<?php

namespace App\Orders;

use App\Inventory\InventoryLifecycleException;
use App\Inventory\OrderInventoryService;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class FulfillmentService
{
    /** @var array<string, array<int, string>> */
    private const TRANSITIONS = [
        'unfulfilled' => ['processing'],
        'processing' => ['shipped'],
        'shipped' => ['fulfilled'],
        'fulfilled' => [],
        'cancelled' => [],
    ];

    public function __construct(private readonly OrderInventoryService $inventory) {}

    public function update(Order $order, string $status, User $actor): Order
    {
        return DB::transaction(function () use ($order, $status, $actor): Order {
            $order = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            if ($order->status === 'cancelled') {
                throw new OrderConflictException('cancelled_order', 'A cancelled order cannot be fulfilled.');
            }
            if ($order->payment_status !== 'paid') {
                throw new OrderConflictException('payment_required', 'The order must be paid before fulfillment can begin.');
            }
            if (! in_array($status, self::TRANSITIONS[$order->fulfillment_status] ?? [], true)) {
                throw new OrderConflictException('invalid_fulfillment_transition', "Fulfillment cannot move from {$order->fulfillment_status} to {$status}.");
            }
            if ($status === 'fulfilled') {
                try {
                    $this->inventory->convertReservationsToSale($order);
                } catch (InventoryLifecycleException $exception) {
                    throw new OrderConflictException($exception->errorCode, $exception->getMessage());
                }
            }
            $metadata = $order->metadata ?? [];
            $history = $metadata['fulfillment_history'] ?? [];
            $history[] = ['from' => $order->fulfillment_status, 'to' => $status, 'changed_at' => now()->utc()->toISOString(), 'actor' => ['id' => $actor->id, 'name' => $actor->name, 'email' => $actor->email]];
            $metadata['fulfillment_history'] = $history;
            $orderStatus = match ($status) {
                'processing' => 'processing',
                'shipped' => 'shipped',
                'fulfilled' => 'completed',
            };
            $order->update(['fulfillment_status' => $status, 'status' => $orderStatus, 'metadata' => $metadata]);

            return $order->fresh(['customer', 'items', 'payments.transactions']);
        });
    }
}

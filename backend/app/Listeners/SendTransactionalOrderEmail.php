<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Events\OrderStatusChanged;
use App\Events\PaymentStatusChanged;
use App\Mail\TransactionalOrderMail;
use App\Models\Order;
use App\Models\TransactionalEmailDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendTransactionalOrderEmail implements ShouldQueue
{
    public string $queue = 'emails';

    public function handle(OrderCreated|PaymentStatusChanged|OrderStatusChanged $event): void
    {
        [$messageType, $eventKey, $payment] = match (true) {
            $event instanceof OrderCreated => ['order_created', 'order-created:'.$event->order->public_id, null],
            $event instanceof PaymentStatusChanged => [$this->paymentMessageType($event->status), 'payment:'.$event->order->public_id.':'.$event->status, $event],
            default => ['status_'.$event->status, 'status:'.$event->order->public_id.':'.$event->status, null],
        };
        $order = $event->order->loadMissing('items');
        $delivery = TransactionalEmailDelivery::query()->firstOrCreate([
            'event_key' => $eventKey,
        ], [
            'recipient' => $order->email,
            'message_type' => $messageType,
        ]);
        if ($delivery->sent_at) {
            return;
        }
        Mail::to($order->email)->send(new TransactionalOrderMail(
            order: $order,
            messageType: $messageType,
            paymentStatus: $payment?->status,
            paymentProvider: $payment?->provider,
            paymentMethod: $payment?->method,
            paidAmount: $payment?->amount,
            paidAt: $payment?->paidAt,
        ));
        $delivery->update(['sent_at' => now()]);
    }

    private function paymentMessageType(string $status): string
    {
        return match ($status) {
            'paid' => 'payment_paid',
            'expired' => 'payment_expired',
            default => 'payment_failed',
        };
    }
}
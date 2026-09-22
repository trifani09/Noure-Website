<?php

namespace App\Payments;

use App\Events\PaymentStatusChanged;
use App\Inventory\InventoryLifecycleException;
use App\Inventory\OrderInventoryService;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentTransaction;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Throwable;

class PaymentService
{
    /** @var array<string, array<int, string>> */
    private const STATUS_TRANSITIONS = [
        'pending' => ['authorized', 'paid', 'failed', 'expired', 'cancelled'],
        'authorized' => ['paid', 'failed', 'cancelled'],
        'paid' => ['partially_refunded', 'refunded'],
        'partially_refunded' => ['refunded'],
        'failed' => [],
        'expired' => [],
        'cancelled' => [],
        'refunded' => [],
    ];

    public function __construct(private readonly PaymentGatewayInterface $gateway, private readonly OrderInventoryService $inventory) {}

    public function create(Order $order, string $idempotencyKey): Payment
    {
        if ($order->status === 'cancelled') {
            throw new PaymentException('order_cancelled', 'This order is cancelled and can no longer be paid.');
        }
        if ($order->payment_status === 'paid' || $order->payments()->where('status', 'paid')->exists()) {
            throw new PaymentException('order_already_paid', 'This order is already fully paid.');
        }
        $existing = Payment::query()->where('idempotency_key', $idempotencyKey)->first();
        if ($existing) {
            if ($existing->order_id !== $order->id) {
                throw new PaymentException('idempotency_conflict', 'The idempotency key belongs to another payment.');
            }
            if ($existing->status !== 'failed' || $existing->failure_code !== 'provider_error') {
                return $existing;
            }
            $existing->update(['status' => 'pending', 'failed_at' => null, 'failure_code' => null, 'failure_message' => null]);
            $payment = $existing;
        } else {
            $payment = Payment::query()->create([
                'order_id' => $order->id, 'provider' => 'midtrans', 'method_type' => 'snap', 'status' => 'pending',
                'amount' => $order->grand_total_amount, 'currency' => $order->currency, 'idempotency_key' => $idempotencyKey, 'metadata' => [],
            ]);
        }
        try {
            $provider = $this->gateway->createPayment($order->loadMissing('items'));
            $payment->update([
                'provider_payment_id' => $order->order_number,
                'metadata' => Arr::only($provider, ['token', 'redirect_url', 'expiry_time']),
            ]);
        } catch (Throwable $exception) {
            $payment->update(['status' => 'failed', 'failed_at' => now(), 'failure_code' => 'provider_error', 'failure_message' => 'Payment provider request failed.']);
            report($exception);
            if (! config('services.midtrans.server_key')) {
                throw new PaymentException('payment_provider_not_configured', 'Midtrans sandbox credentials are not configured.', 503);
            }
            throw new PaymentException('payment_provider_unavailable', 'The payment provider is temporarily unavailable.', 502);
        }

        return $payment->fresh('transactions');
    }

    /** @param array<string, mixed> $payload */
    public function webhook(array $payload): Payment
    {
        if (! $this->gateway->verifyWebhook($payload)) {
            throw new PaymentException('invalid_webhook_signature', 'Webhook signature is invalid.', 401);
        }

        return DB::transaction(function () use ($payload): Payment {
            $payment = Payment::query()->where('provider', 'midtrans')->where('provider_payment_id', $payload['order_id'])->lockForUpdate()->first();
            if (! $payment) {
                throw new PaymentException('payment_not_found', 'Payment was not found.', 404);
            }
            if ((int) round((float) $payload['gross_amount']) !== $payment->amount || ($payload['currency'] ?? 'IDR') !== $payment->currency) {
                throw new PaymentException('payment_amount_mismatch', 'Webhook amount or currency does not match the payment.', 409);
            }
            $status = $this->gateway->status($payload);
            $previousStatus = $payment->status;
            $transactionId = (string) ($payload['transaction_id'] ?? $payload['order_id']);
            $idempotencyKey = 'midtrans:'.$transactionId.':'.$status;
            if (PaymentTransaction::query()->where('idempotency_key', $idempotencyKey)->exists()) {
                return $payment->load('transactions');
            }
            PaymentTransaction::query()->create([
                'payment_id' => $payment->id, 'type' => 'notification', 'status' => $status,
                'amount' => $payment->amount, 'currency' => $payment->currency,
                'provider_transaction_id' => $transactionId.'-'.$status, 'idempotency_key' => $idempotencyKey,
                'response_metadata' => Arr::except($payload, ['signature_key']), 'processed_at' => now(),
            ]);
            $mayTransition = $status === $payment->status || in_array($status, self::STATUS_TRANSITIONS[$payment->status] ?? [], true);
            $changes = ['method_type' => $payload['payment_type'] ?? $payment->method_type];
            if ($mayTransition) {
                $changes['status'] = $status;
            }
            if ($mayTransition && $status === 'paid') {
                try {
                    $this->inventory->convertReservationsToSale($payment->order);
                } catch (InventoryLifecycleException $exception) {
                    throw new PaymentException($exception->errorCode, $exception->getMessage());
                }
                $changes['paid_at'] = now();
            } elseif ($mayTransition && in_array($status, ['failed', 'cancelled', 'expired'], true)) {
                try {
                    $this->inventory->releaseReservations($payment->order, reason: 'Released after payment '.$status);
                } catch (InventoryLifecycleException $exception) {
                    throw new PaymentException($exception->errorCode, $exception->getMessage());
                }
                $changes['failed_at'] = now();
                $changes['failure_code'] = (string) ($payload['transaction_status'] ?? $status);
                $changes['failure_message'] = mb_substr((string) ($payload['status_message'] ?? 'Payment was not successful.'), 0, 500);
            }
            $payment->update($changes);
            if ($mayTransition) {
                $payment->order()->update(['payment_status' => $status]);
                if ($status !== $previousStatus) {
                    $payment = $payment->fresh(['order']);
                    PaymentStatusChanged::dispatch(
                        $payment->order->load('items'),
                        $status,
                        $payment->provider,
                        $payment->method_type,
                        $payment->amount,
                        $payment->paid_at?->utc()->toISOString(),
                    );
                }
            }

            return $payment->fresh('transactions');
        });
    }
}

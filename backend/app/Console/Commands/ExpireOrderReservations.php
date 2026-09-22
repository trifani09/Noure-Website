<?php

namespace App\Console\Commands;

use App\Events\OrderStatusChanged;
use App\Events\PaymentStatusChanged;
use App\Inventory\OrderInventoryService;
use App\Models\Order;
use App\Models\PaymentTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ExpireOrderReservations extends Command
{
    protected $signature = 'orders:expire-reservations {--chunk=100}';

    protected $description = 'Expire unpaid orders and release their inventory reservations';

    public function __construct(private readonly OrderInventoryService $inventory)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $expired = 0;
        $failed = 0;
        $cutoff = now()->subMinutes(config('orders.reservation_minutes'));
        Order::query()->whereIn('status', ['pending', 'processing'])->whereIn('payment_status', ['unpaid', 'pending', 'authorized'])
            ->where('created_at', '<=', $cutoff)->orderBy('id')->chunkById((int) $this->option('chunk'), function ($orders) use (&$expired, &$failed): void {
                foreach ($orders as $candidate) {
                    try {
                        $notification = DB::transaction(function () use ($candidate): ?array {
                            $order = Order::query()->whereKey($candidate->id)->lockForUpdate()->first();
                            if (! $order || ! in_array($order->payment_status, ['unpaid', 'pending', 'authorized'], true) || in_array($order->status, ['cancelled', 'completed'], true)) {
                                return null;
                            }
                            $this->inventory->releaseReservations($order, reason: 'Released after reservation expiry');
                            $payment = $order->payments()->latest()->lockForUpdate()->first();
                            if ($payment && ! in_array($payment->status, ['paid', 'expired'], true)) {
                                $payment->update(['status' => 'expired', 'failed_at' => now(), 'failure_code' => 'reservation_expired', 'failure_message' => 'Order reservation expired before payment.']);
                                PaymentTransaction::query()->firstOrCreate(
                                    ['idempotency_key' => 'reservation-expiry:'.$payment->public_id],
                                    ['payment_id' => $payment->id, 'type' => 'expiry', 'status' => 'expired', 'amount' => $payment->amount, 'currency' => $payment->currency, 'provider_transaction_id' => 'reservation-expiry-'.$payment->public_id, 'response_metadata' => ['source' => 'scheduler'], 'processed_at' => now()],
                                );
                            }
                            $order->update(['status' => 'cancelled', 'payment_status' => 'expired', 'fulfillment_status' => 'cancelled', 'cancelled_at' => now()]);

                            return [
                                'order' => $order->fresh(['items']),
                                'payment' => $payment && $payment->status === 'expired' ? $payment : null,
                            ];
                        });
                        if ($notification) {
                            if ($notification['payment']) {
                                PaymentStatusChanged::dispatch($notification['order'], 'expired', $notification['payment']->provider, $notification['payment']->method_type, $notification['payment']->amount, null);
                            }
                            OrderStatusChanged::dispatch($notification['order'], 'cancelled');
                        }
                        $expired++;
                    } catch (Throwable $exception) {
                        report($exception);
                        $failed++;
                    }
                }
            });
        $this->info("Expired {$expired} order reservation(s); {$failed} failed.");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}

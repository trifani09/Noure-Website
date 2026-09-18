<?php

namespace App\Payments;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

class MidtransPaymentGateway implements PaymentGatewayInterface
{
    /** @return array<string, mixed> */
    public function createPayment(Order $order): array
    {
        if (! config('services.midtrans.server_key')) {
            throw new \RuntimeException('Midtrans server key is not configured.');
        }
        $response = Http::withBasicAuth((string) config('services.midtrans.server_key'), '')
            ->acceptJson()->asJson()->timeout(15)
            ->post($this->snapUrl().'/snap/v1/transactions', [
                'transaction_details' => ['order_id' => $order->order_number, 'gross_amount' => $order->grand_total_amount],
                'customer_details' => ['email' => $order->email, 'phone' => $order->phone],
                'callbacks' => ['finish' => rtrim((string) config('services.midtrans.storefront_url'), '/').'/payment/'.$order->public_id],
            ])->throw();

        return $response->json();
    }

    public function verifyWebhook(array $payload): bool
    {
        if (! config('services.midtrans.server_key')) {
            return false;
        }
        $required = ['order_id', 'status_code', 'gross_amount', 'signature_key'];
        foreach ($required as $field) {
            if (! isset($payload[$field]) || ! is_string($payload[$field])) {
                return false;
            }
        }
        $expected = hash('sha512', $payload['order_id'].$payload['status_code'].$payload['gross_amount'].config('services.midtrans.server_key'));

        return hash_equals($expected, $payload['signature_key']);
    }

    public function status(array $payload): string
    {
        return match ($payload['transaction_status'] ?? null) {
            'authorize' => 'authorized',
            'capture' => ($payload['fraud_status'] ?? 'accept') === 'accept' ? 'paid' : 'failed',
            'settlement' => 'paid',
            'deny', 'failure', 'expire' => 'failed',
            'cancel' => 'cancelled',
            'partial_refund' => 'partially_refunded',
            'refund' => 'refunded',
            default => 'pending',
        };
    }

    private function snapUrl(): string
    {
        return config('services.midtrans.production') ? 'https://app.midtrans.com' : 'https://app.sandbox.midtrans.com';
    }
}

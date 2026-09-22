<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'order_public_id' => $this->order->public_id,
            'order_number' => $this->order->order_number,
            'provider' => $this->provider,
            'provider_payment_id' => $this->provider_payment_id,
            'method_type' => $this->method_type,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'redirect_url' => $this->metadata['redirect_url'] ?? null,
            'token' => $this->metadata['token'] ?? null,
            'expires_at' => $this->metadata['expiry_time'] ?? null,
            'paid_at' => $this->paid_at?->utc()->toISOString(),
            'retryable' => $this->status === 'failed' && $this->failure_code === 'provider_error',
        ];
    }
}

<?php

namespace App\Payments;

use App\Models\Order;

interface PaymentGatewayInterface
{
    /** @return array<string, mixed> */
    public function createPayment(Order $order): array;

    /** @param array<string, mixed> $payload */
    public function verifyWebhook(array $payload): bool;

    /** @param array<string, mixed> $payload */
    public function status(array $payload): string;
}

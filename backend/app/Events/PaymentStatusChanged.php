<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

class PaymentStatusChanged implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public readonly Order $order, public readonly string $status, public readonly ?string $provider = null, public readonly ?string $method = null, public readonly ?int $amount = null, public readonly ?string $paidAt = null) {}
}

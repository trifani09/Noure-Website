<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class OrderStatusChanged implements ShouldDispatchAfterCommit
{
    public function __construct(public readonly Order $order, public readonly string $status) {}
}
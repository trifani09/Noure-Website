<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;

class OrderCreated implements ShouldDispatchAfterCommit
{
    public function __construct(public readonly Order $order) {}
}
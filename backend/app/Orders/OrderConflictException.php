<?php

namespace App\Orders;

use RuntimeException;

class OrderConflictException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}

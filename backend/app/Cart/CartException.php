<?php

namespace App\Cart;

use RuntimeException;

class CartException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message, public readonly int $status)
    {
        parent::__construct($message);
    }
}

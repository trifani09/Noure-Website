<?php

namespace App\Inventory;

use RuntimeException;

class InventoryLifecycleException extends RuntimeException
{
    public function __construct(public readonly string $errorCode, string $message)
    {
        parent::__construct($message);
    }
}

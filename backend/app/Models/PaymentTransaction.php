<?php

namespace App\Models;

use Database\Factories\PaymentTransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['payment_id', 'type', 'status', 'amount', 'currency', 'provider_transaction_id', 'idempotency_key', 'response_metadata', 'processed_at'])]
class PaymentTransaction extends Model
{
    /** @use HasFactory<PaymentTransactionFactory> */
    use HasFactory;

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    protected function casts(): array
    {
        return ['amount' => 'integer', 'response_metadata' => 'array', 'processed_at' => 'datetime'];
    }
}

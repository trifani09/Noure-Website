<?php

namespace App\Models;

use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['customer_id', 'label', 'recipient_name', 'phone', 'line1', 'line2', 'city', 'province', 'postal_code', 'country_code', 'is_default_shipping', 'is_default_billing'])]
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory, HasUlids, SoftDeletes;

    public function uniqueIds(): array
    {
        return ['public_id'];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    protected function casts(): array
    {
        return ['is_default_shipping' => 'boolean', 'is_default_billing' => 'boolean'];
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['store_name', 'announcement_text', 'announcement_url', 'announcement_is_active', 'support_email', 'support_phone', 'whatsapp_number', 'instagram_url', 'default_currency', 'timezone', 'low_stock_threshold', 'order_prefix'])]
class StoreSetting extends Model
{
    protected function casts(): array
    {
        return ['announcement_is_active' => 'boolean', 'low_stock_threshold' => 'integer'];
    }
}

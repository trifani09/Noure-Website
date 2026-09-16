<?php

namespace App\Models;

use Database\Factories\InventoryLevelFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['variant_id', 'location_id', 'on_hand', 'reserved', 'safety_stock', 'version'])]
class InventoryLevel extends Model
{
    /** @use HasFactory<InventoryLevelFactory> */
    use HasFactory;

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    protected function available(): Attribute
    {
        return Attribute::get(fn (): int => max($this->on_hand - $this->reserved - $this->safety_stock, 0));
    }

    protected function casts(): array
    {
        return ['on_hand' => 'integer', 'reserved' => 'integer', 'safety_stock' => 'integer', 'version' => 'integer'];
    }
}

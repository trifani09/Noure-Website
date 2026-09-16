<?php

namespace App\Models;

use Database\Factories\InventoryLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'address', 'contact', 'is_active'])]
class InventoryLocation extends Model
{
    /** @use HasFactory<InventoryLocationFactory> */
    use HasFactory;

    public function inventoryLevels(): HasMany
    {
        return $this->hasMany(InventoryLevel::class, 'location_id');
    }

    protected function casts(): array
    {
        return ['address' => 'array', 'contact' => 'array', 'is_active' => 'boolean'];
    }
}

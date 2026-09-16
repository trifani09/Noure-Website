<?php

namespace App\Models;

use Database\Factories\ProductOptionValueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['product_option_id', 'label', 'code', 'swatch_value', 'sort_order'])]
class ProductOptionValue extends Model
{
    /** @use HasFactory<ProductOptionValueFactory> */
    use HasFactory;

    public function option(): BelongsTo
    {
        return $this->belongsTo(ProductOption::class, 'product_option_id');
    }

    public function variants(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'variant_option_values', 'product_option_value_id', 'variant_id');
    }

    protected function casts(): array
    {
        return ['sort_order' => 'integer'];
    }
}

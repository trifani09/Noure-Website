<?php

namespace App\Http\Resources\Api\V1\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'sku' => $this->sku,
            'title' => $this->title,
            'option_values' => $this->optionValues->mapWithKeys(fn ($value) => [$value->option->code => $value->code]),
            'price_amount' => $this->price_amount,
            'compare_at_amount' => $this->compare_at_amount,
            'currency' => $this->currency,
            'barcode' => $this->barcode,
            'weight_grams' => $this->weight_grams,
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'created_at' => $this->created_at?->utc()->toISOString(),
            'updated_at' => $this->updated_at?->utc()->toISOString(),
        ];
    }
}

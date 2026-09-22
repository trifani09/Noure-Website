<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AddressResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'public_id' => $this->public_id,
            'label' => $this->label,
            'recipient_name' => $this->recipient_name,
            'phone' => $this->phone,
            'line1' => $this->line1,
            'line2' => $this->line2,
            'city' => $this->city,
            'province' => $this->province,
            'postal_code' => $this->postal_code,
            'country_code' => $this->country_code,
            'is_default_shipping' => (bool) $this->is_default_shipping,
            'is_default_billing' => (bool) $this->is_default_billing,
        ];
    }
}
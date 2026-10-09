<?php

namespace App\Http\Requests\Api\V1\Admin;

use App\Http\Requests\Api\V1\CatalogRequest;

class UpdateStoreSettingRequest extends CatalogRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['store_name' => ['required', 'string', 'max:160'], 'announcement_text' => ['nullable', 'string', 'max:255'], 'announcement_url' => ['nullable', 'string', 'max:2048', 'starts_with:/'], 'announcement_is_active' => ['required', 'boolean'], 'support_email' => ['nullable', 'email', 'max:255'], 'support_phone' => ['nullable', 'string', 'max:50'], 'whatsapp_number' => ['nullable', 'string', 'max:50'], 'instagram_url' => ['nullable', 'url', 'max:2048'], 'default_currency' => ['required', 'string', 'size:3'], 'timezone' => ['required', 'timezone'], 'low_stock_threshold' => ['required', 'integer', 'min:0', 'max:100000'], 'order_prefix' => ['required', 'alpha_num', 'max:20']];
    }
}

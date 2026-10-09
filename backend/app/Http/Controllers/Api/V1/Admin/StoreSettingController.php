<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\UpdateStoreSettingRequest;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;

class StoreSettingController extends Controller
{
    public function show(): JsonResponse
    {
        return $this->response($this->settings());
    }

    public function update(UpdateStoreSettingRequest $request): JsonResponse
    {
        $settings = $this->settings();
        $settings->update($request->validated());

        return $this->response($settings->refresh());
    }

    private function settings(): StoreSetting
    {
        return StoreSetting::query()->firstOrCreate([], ['store_name' => 'Noure']);
    }

    private function response(StoreSetting $settings): JsonResponse
    {
        return response()->json(['data' => $settings->only(['store_name', 'announcement_text', 'announcement_url', 'announcement_is_active', 'support_email', 'support_phone', 'whatsapp_number', 'instagram_url', 'default_currency', 'timezone', 'low_stock_threshold', 'order_prefix']), 'meta' => (object) [], 'message' => null]);
    }
}

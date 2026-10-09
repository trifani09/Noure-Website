<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\StoreSetting;
use Illuminate\Http\JsonResponse;

class StorefrontSettingController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $settings = StoreSetting::query()->firstOrCreate([], ['store_name' => 'Noure']);

        return response()->json([
            'data' => $settings->only(['store_name', 'announcement_text', 'announcement_url', 'announcement_is_active', 'support_email', 'support_phone', 'whatsapp_number', 'instagram_url', 'default_currency']),
            'meta' => (object) [],
            'message' => null,
        ]);
    }
}

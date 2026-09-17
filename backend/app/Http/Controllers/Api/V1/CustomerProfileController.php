<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateCustomerProfileRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return $this->response($request);
    }

    public function update(UpdateCustomerProfileRequest $request): JsonResponse
    {
        $customer = Auth::guard('customer')->user();
        $customer->update($request->validated());

        return $this->response($request, 'Profile updated successfully.');
    }

    private function response(Request $request, ?string $message = null): JsonResponse
    {
        return response()->json([
            'data' => (new CustomerResource(Auth::guard('customer')->user()))->resolve($request),
            'meta' => (object) [],
            'message' => $message,
        ]);
    }
}

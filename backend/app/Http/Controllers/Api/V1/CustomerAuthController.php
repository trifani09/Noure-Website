<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CustomerLoginRequest;
use App\Http\Requests\Api\V1\RegisterCustomerRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerAuthController extends Controller
{
    public function register(RegisterCustomerRequest $request): JsonResponse
    {
        $customer = Customer::query()->create([
            ...$request->safe()->only(['first_name', 'last_name', 'email', 'phone', 'password']),
            'status' => 'active',
        ]);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return $this->customerResponse($request, $customer, 201);
    }

    public function login(CustomerLoginRequest $request): JsonResponse
    {
        $credentials = [
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'status' => 'active',
        ];

        if (! Auth::guard('customer')->attempt($credentials, (bool) $request->validated('remember', false))) {
            return $this->unauthenticated();
        }

        $request->session()->regenerate();

        return $this->customerResponse($request, Auth::guard('customer')->user());
    }

    public function me(Request $request): JsonResponse
    {
        return $this->customerResponse($request, Auth::guard('customer')->user());
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'data' => null,
            'meta' => (object) [],
            'message' => 'Logged out successfully.',
        ]);
    }

    private function customerResponse(Request $request, Customer $customer, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => (new CustomerResource($customer))->resolve($request),
            'meta' => (object) [],
            'message' => null,
        ], $status);
    }

    private function unauthenticated(): JsonResponse
    {
        return response()->json([
            'data' => null,
            'meta' => ['errors' => [[
                'code' => 'unauthenticated',
                'message' => 'The provided credentials are invalid.',
            ]]],
            'message' => 'Authentication is required.',
        ], 401);
    }
}

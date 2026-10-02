<?php

namespace App\Http\Controllers\Api\V1;

use App\Cart\CartService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CustomerLoginRequest;
use App\Http\Requests\Api\V1\ForgotCustomerPasswordRequest;
use App\Http\Requests\Api\V1\RegisterCustomerRequest;
use App\Http\Requests\Api\V1\ResetCustomerPasswordRequest;
use App\Http\Resources\Api\V1\CustomerResource;
use App\Models\Customer;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;

class CustomerAuthController extends Controller
{
    public function __construct(private readonly CartService $carts) {}

    public function register(RegisterCustomerRequest $request): JsonResponse
    {
        $guestCartToken = $request->cookie('noure_cart');
        $customer = Customer::query()->create([
            ...$request->safe()->only(['first_name', 'last_name', 'email', 'phone', 'password']),
            'status' => 'active',
        ]);

        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();
        $this->carts->claimGuestCart($customer, $guestCartToken);

        return $this->customerResponse($request, $customer, 201);
    }

    public function login(CustomerLoginRequest $request): JsonResponse
    {
        $guestCartToken = $request->cookie('noure_cart');
        $credentials = [
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'status' => 'active',
        ];

        if (! Auth::guard('customer')->attempt($credentials, (bool) $request->validated('remember', false))) {
            return $this->unauthenticated();
        }

        $request->session()->regenerate();
        $this->carts->claimGuestCart(Auth::guard('customer')->user(), $guestCartToken);

        return $this->customerResponse($request, Auth::guard('customer')->user());
    }

    public function forgotPassword(ForgotCustomerPasswordRequest $request): JsonResponse
    {
        Password::broker('customers')->sendResetLink($request->validated());

        return response()->json([
            'data' => null,
            'meta' => (object) [],
            'message' => 'If the account exists, a password reset link has been sent.',
        ]);
    }

    public function resetPassword(ResetCustomerPasswordRequest $request): JsonResponse
    {
        $status = Password::broker('customers')->reset($request->validated(), function (Customer $customer, string $password): void {
            $customer->forceFill(['password' => $password])->save();
            event(new PasswordReset($customer));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return response()->json([
                'data' => null,
                'meta' => ['errors' => [[
                    'code' => 'invalid_reset_token',
                    'message' => 'This password reset link is invalid or has expired.',
                ]]],
                'message' => 'Unable to reset the password.',
            ], 422);
        }

        return response()->json([
            'data' => null,
            'meta' => (object) [],
            'message' => 'Your password has been reset successfully.',
        ]);
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

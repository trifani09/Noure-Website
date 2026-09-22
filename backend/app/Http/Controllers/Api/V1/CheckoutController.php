<?php

namespace App\Http\Controllers\Api\V1;

use App\Cart\CartService;
use App\Checkout\CheckoutException;
use App\Checkout\OrderService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOrderRequest;
use App\Http\Requests\Api\V1\ShippingMethodsRequest;
use App\Http\Resources\Api\V1\CartResource;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Cart;
use App\Models\Customer;
use App\Shipping\ShippingRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    private const COOKIE = 'noure_cart';

    public function __construct(private readonly CartService $carts, private readonly OrderService $orders, private readonly ShippingRateService $shipping) {}

    public function show(Request $request): JsonResponse
    {
        [$cart, $token] = $this->cart($request);
        $customer = $this->customer();
        $cartData = (new CartResource($cart))->resolve($request);
        $data = [
            'cart' => $cartData,
            'customer' => $customer ? ['name' => trim($customer->first_name.' '.$customer->last_name), 'email' => $customer->email, 'phone' => $customer->phone] : null,
            'addresses' => $customer?->addresses()->orderByDesc('is_default_shipping')->get()->map(fn ($address): array => [
                'public_id' => $address->public_id,
                ...$address->only(['label', 'recipient_name', 'phone', 'line1', 'line2', 'city', 'province', 'postal_code', 'country_code', 'is_default_shipping']),
            ])->values() ?? [],
            'totals' => ['subtotal_amount' => $cartData['subtotal_amount'], 'discount_amount' => 0, 'shipping_amount' => 0, 'grand_total_amount' => $cartData['subtotal_amount'], 'currency' => $cartData['currency']],
        ];

        return $this->response($data, $token);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        $token = null;
        try {
            $token = $request->cookie(self::COOKIE);
            $cart = $this->orderCart($request);
            $order = $this->orders->create($cart, $this->customer(), $request->validated());
        } catch (CheckoutException $exception) {
            return $this->failure($exception, $token);
        }

        return $this->response((new OrderResource($order))->resolve($request), $token, 201, 'Order placed.');
    }

    public function shippingMethods(ShippingMethodsRequest $request): JsonResponse
    {
        $token = null;
        try {
            [$cart, $token] = $this->cart($request);
            $data = $this->shipping->availableMethods($cart, $this->destination($request));
        } catch (CheckoutException $exception) {
            return $this->failure($exception, $token);
        }

        return $this->response($data, $token);
    }

    private function cart(Request $request): array
    {
        return $this->carts->resolve($this->customer(), $request->cookie(self::COOKIE));
    }

    private function orderCart(Request $request): Cart
    {
        $customer = $this->customer();
        if ($customer) {
            $cart = Cart::query()->where('customer_id', $customer->id)->where('status', 'active')->first();
            if (! $cart) {
                throw new CheckoutException('cart_not_found', 'No active cart was found.', 404);
            }
        } else {
            $token = $request->cookie(self::COOKIE);
            if (! $token) {
                throw new CheckoutException('cart_not_found', 'No active cart was found.', 404);
            }
            $cart = Cart::query()->where('guest_token_hash', hash('sha256', $token))->first();
            if (! $cart) {
                throw new CheckoutException('cart_not_found', 'No active cart was found.', 404);
            }
        }
        if ($cart->status === 'active' && $cart->expires_at?->isPast()) {
            throw new CheckoutException('cart_expired', 'Your cart session has expired.');
        }
        if ($cart->status !== 'active') {
            throw new CheckoutException('cart_expired', 'Your cart is no longer active.');
        }

        return $cart;
    }

    private function customer(): ?Customer
    {
        /** @var Customer|null $customer */
        $customer = Auth::guard('customer')->user();

        return $customer;
    }

    /** @return array<string, mixed> */
    private function destination(Request $request): array
    {
        if ($request->filled('address_public_id')) {
            $customer = $this->customer();
            $address = $customer?->addresses()->where('public_id', $request->string('address_public_id'))->first();
            if (! $address) {
                throw new CheckoutException('invalid_address', 'The selected shipping address is not available.', 422);
            }

            return $address->only(['city', 'province', 'postal_code', 'country_code']);
        }

        return $request->validated();
    }

    private function response(array $data, ?string $token, int $status = 200, ?string $message = null): JsonResponse
    {
        $response = response()->json(['data' => $data, 'meta' => (object) [], 'message' => $message], $status);

        return $token ? $response->cookie(self::COOKIE, $token, 60 * 24 * 30, '/', null, app()->isProduction(), true, false, 'lax') : $response;
    }

    private function failure(CheckoutException $exception, ?string $token): JsonResponse
    {
        $response = response()->json(['data' => null, 'meta' => ['errors' => [['code' => $exception->errorCode, 'message' => $exception->getMessage()]]], 'message' => $exception->getMessage()], $exception->status);

        return $token ? $response->cookie(self::COOKIE, $token, 60 * 24 * 30, '/', null, app()->isProduction(), true, false, 'lax') : $response;
    }
}

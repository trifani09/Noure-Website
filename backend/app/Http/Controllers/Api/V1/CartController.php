<?php

namespace App\Http\Controllers\Api\V1;

use App\Cart\CartException;
use App\Cart\CartService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCartItemRequest;
use App\Http\Requests\Api\V1\UpdateCartItemRequest;
use App\Http\Resources\Api\V1\CartResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CartController extends Controller
{
    private const COOKIE = 'noure_cart';

    public function __construct(private readonly CartService $carts) {}

    public function show(Request $request): JsonResponse
    {
        [$cart, $token] = $this->cart($request);

        return $this->response($request, $cart, $token);
    }

    public function store(StoreCartItemRequest $request): JsonResponse
    {
        [$cart, $token] = $this->cart($request);
        try {
            $cart = $this->carts->add($cart, $request->validated('variant_public_id'), $request->integer('quantity'));
        } catch (CartException $exception) {
            return $this->failure($exception, $token);
        }

        return $this->response($request, $cart, $token, 201, 'Added to cart.');
    }

    public function update(UpdateCartItemRequest $request, int $id): JsonResponse
    {
        [$cart, $token] = $this->cart($request);
        try {
            $cart = $this->carts->update($cart, $id, $request->integer('quantity'));
        } catch (CartException $exception) {
            return $this->failure($exception, $token);
        }

        return $this->response($request, $cart, $token, message: 'Cart updated.');
    }

    public function destroy(Request $request, int $id): Response|JsonResponse
    {
        [$cart, $token] = $this->cart($request);
        try {
            $this->carts->remove($cart, $id);
        } catch (CartException $exception) {
            return $this->failure($exception, $token);
        }

        return response()->noContent();
    }

    private function cart(Request $request): array
    {
        /** @var Customer|null $customer */
        $customer = Auth::guard('customer')->user();

        return $this->carts->resolve($customer, $request->cookie(self::COOKIE));
    }

    private function response(Request $request, $cart, ?string $token, int $status = 200, ?string $message = null): JsonResponse
    {
        $response = response()->json(['data' => (new CartResource($cart))->resolve($request), 'meta' => (object) [], 'message' => $message], $status);

        return $token ? $response->cookie(self::COOKIE, $token, 60 * 24 * 30, '/', null, app()->isProduction(), true, false, 'lax') : $response;
    }

    private function failure(CartException $exception, ?string $token): JsonResponse
    {
        $response = response()->json(['data' => null, 'meta' => ['errors' => [['code' => $exception->errorCode, 'message' => $exception->getMessage()]]], 'message' => $exception->getMessage()], $exception->status);

        return $token ? $response->cookie(self::COOKIE, $token, 60 * 24 * 30, '/', null, app()->isProduction(), true, false, 'lax') : $response;
    }
}

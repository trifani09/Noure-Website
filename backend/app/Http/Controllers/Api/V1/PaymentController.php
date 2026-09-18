<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PaymentResource;
use App\Models\Customer;
use App\Models\Order;
use App\Payments\PaymentException;
use App\Payments\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $payments) {}

    public function store(Request $request, string $order_public_id): JsonResponse
    {
        $order = $this->ownedOrder($request, $order_public_id);
        if (! $order) {
            return $this->failure(new PaymentException('order_not_found', 'The requested order was not found.', 404));
        }
        $key = $request->header('Idempotency-Key', 'order:'.$order->public_id.':payment');
        if (strlen($key) > 255) {
            return $this->failure(new PaymentException('invalid_idempotency_key', 'The idempotency key is too long.', 422));
        }
        try {
            $payment = $this->payments->create($order, $key);
        } catch (PaymentException $exception) {
            return $this->failure($exception);
        }

        return response()->json(['data' => (new PaymentResource($payment->load('order')))->resolve($request), 'meta' => (object) [], 'message' => 'Payment created.'], 201);
    }

    public function show(Request $request, string $order_public_id): JsonResponse
    {
        $order = $this->ownedOrder($request, $order_public_id);
        if (! $order) {
            return $this->failure(new PaymentException('order_not_found', 'The requested order was not found.', 404));
        }
        $payment = $order->payments()->latest()->first();
        if (! $payment) {
            return $this->failure(new PaymentException('payment_not_found', 'No payment exists for this order.', 404));
        }

        return response()->json(['data' => (new PaymentResource($payment->load('order')))->resolve($request), 'meta' => (object) [], 'message' => null]);
    }

    public function webhook(Request $request): JsonResponse
    {
        try {
            $payment = $this->payments->webhook($request->all());
        } catch (PaymentException $exception) {
            return $this->failure($exception);
        }

        return response()->json(['data' => ['payment_public_id' => $payment->public_id, 'status' => $payment->status], 'meta' => (object) [], 'message' => 'Notification accepted.']);
    }

    private function ownedOrder(Request $request, string $publicId): ?Order
    {
        /** @var Customer|null $customer */
        $customer = Auth::guard('customer')->user();
        $query = Order::query()->where('public_id', $publicId);
        if ($customer) {
            return $query->where('customer_id', $customer->id)->first();
        }
        $token = $request->cookie('noure_cart');
        if (! $token) {
            return null;
        }

        return $query->whereHas('cart', fn ($cart) => $cart->where('guest_token_hash', hash('sha256', $token)))->first();
    }

    private function failure(PaymentException $exception): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['errors' => [['code' => $exception->errorCode, 'message' => $exception->getMessage()]]], 'message' => $exception->getMessage()], $exception->status);
    }
}

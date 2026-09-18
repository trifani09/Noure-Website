<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListOrdersRequest;
use App\Http\Requests\Api\V1\Admin\UpdateOrderStatusRequest;
use App\Http\Resources\Api\V1\Admin\AdminOrderListResource;
use App\Http\Resources\Api\V1\Admin\AdminOrderResource;
use App\Models\User;
use App\Orders\AdminOrderQuery;
use App\Orders\AdminOrderService;
use App\Orders\OrderConflictException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly AdminOrderQuery $orders, private readonly AdminOrderService $service) {}

    public function index(ListOrdersRequest $request): JsonResponse
    {
        $paginator = $this->orders->paginate($request->validated());

        return response()->json(['data' => AdminOrderListResource::collection($paginator->getCollection())->resolve($request), 'meta' => ['pagination' => ['total' => $paginator->total(), 'per_page' => $paginator->perPage(), 'current_page' => $paginator->currentPage(), 'last_page' => max(1, $paginator->lastPage())]], 'message' => null]);
    }

    public function show(Request $request, string $order_public_id): JsonResponse
    {
        $order = $this->orders->find($order_public_id);

        return $order ? $this->resource($request, $order) : $this->notFound();
    }

    public function updateStatus(UpdateOrderStatusRequest $request, string $order_public_id): JsonResponse
    {
        $order = $this->orders->find($order_public_id);
        if (! $order) {
            return $this->notFound();
        }
        /** @var User $actor */
        $actor = $request->user();
        try {
            $order = $this->service->updateStatus($order, $request->validated('status'), $actor);
        } catch (OrderConflictException $exception) {
            return response()->json(['data' => null, 'meta' => ['errors' => [['code' => $exception->errorCode, 'message' => $exception->getMessage()]]], 'message' => 'The request conflicts with the current order state.'], 409);
        }

        return $this->resource($request, $order, 'Order status updated.');
    }

    private function resource(Request $request, object $order, ?string $message = null): JsonResponse
    {
        return response()->json(['data' => (new AdminOrderResource($order))->resolve($request), 'meta' => (object) [], 'message' => $message]);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['errors' => [['code' => 'order_not_found', 'message' => 'The requested order was not found.']]], 'message' => 'The requested resource was not found.'], 404);
    }
}

<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListCustomerOrdersRequest;
use App\Http\Resources\Api\V1\CustomerOrderListResource;
use App\Http\Resources\Api\V1\CustomerOrderResource;
use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerOrderController extends Controller
{
    public function index(ListCustomerOrdersRequest $request): JsonResponse
    {
        $query = $this->customer()->orders()->with('items');
        if ($request->filled('status')) $query->where('status', $request->string('status'));
        if ($request->filled('payment_status')) $query->where('payment_status', $request->string('payment_status'));
        $query->orderBy($request->input('sort', 'newest') === 'oldest' ? 'created_at' : 'created_at', $request->input('sort', 'newest') === 'oldest' ? 'asc' : 'desc')->orderBy('id', 'desc');
        $orders = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'data' => CustomerOrderListResource::collection($orders->getCollection())->resolve($request),
            'meta' => ['pagination' => ['total' => $orders->total(), 'per_page' => $orders->perPage(), 'current_page' => $orders->currentPage(), 'last_page' => max(1, $orders->lastPage())]],
            'message' => null,
        ]);
    }

    public function show(Request $request, string $order_public_id): JsonResponse
    {
        $order = $this->customer()->orders()->with('items')->where('public_id', $order_public_id)->first();
        if (! $order) {
            return response()->json(['data' => null, 'meta' => ['errors' => [['code' => 'order_not_found', 'message' => 'The requested order was not found.']]], 'message' => 'The requested order was not found.'], 404);
        }

        return response()->json(['data' => (new CustomerOrderResource($order))->resolve($request), 'meta' => (object) [], 'message' => null]);
    }

    private function customer(): Customer
    {
        return Auth::guard('customer')->user();
    }
}
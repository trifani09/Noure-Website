<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListCustomersRequest;
use App\Http\Requests\Api\V1\Admin\UpdateCustomerStatusRequest;
use App\Http\Resources\Api\V1\Admin\CustomerResource;
use App\Models\Customer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(ListCustomersRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $query = Customer::query()->withCount('orders')->withSum(['orders as total_spent' => fn (Builder $query) => $query->where('payment_status', 'paid')], 'grand_total_amount');
        if (isset($filters['search'])) {
            $search = addcslashes($filters['search'], '\\%_');
            $query->where(fn (Builder $query) => $query->where('first_name', 'like', "%{$search}%")->orWhere('last_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")->orWhere('phone', 'like', "%{$search}%"));
        }
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->oldest(), 'name_asc' => $query->orderBy('first_name')->orderBy('last_name'), 'name_desc' => $query->orderByDesc('first_name')->orderByDesc('last_name'), 'orders_desc' => $query->orderByDesc('orders_count'), 'spent_desc' => $query->orderByDesc('total_spent'), default => $query->latest()
        };
        $paginator = $query->paginate($filters['per_page'] ?? 20);

        return response()->json(['data' => CustomerResource::collection($paginator->getCollection())->resolve($request), 'meta' => ['pagination' => ['total' => $paginator->total(), 'per_page' => $paginator->perPage(), 'current_page' => $paginator->currentPage(), 'last_page' => max(1, $paginator->lastPage())]], 'message' => null]);
    }

    public function show(Request $request, string $customer): JsonResponse
    {
        $record = $this->find($customer, true);

        return $record ? $this->response($request, $record) : $this->notFound();
    }

    public function updateStatus(UpdateCustomerStatusRequest $request, string $customer): JsonResponse
    {
        $record = $this->find($customer);
        if (! $record) {
            return $this->notFound();
        } $record->update($request->validated());

        return $this->response($request, $this->find($customer, true));
    }

    private function find(string $publicId, bool $addresses = false): ?Customer
    {
        return Customer::query()->when($addresses, fn (Builder $query) => $query->with('addresses'))->withCount('orders')->withSum(['orders as total_spent' => fn (Builder $query) => $query->where('payment_status', 'paid')], 'grand_total_amount')->where('public_id', $publicId)->first();
    }

    private function response(Request $request, Customer $customer): JsonResponse
    {
        return response()->json(['data' => (new CustomerResource($customer))->resolve($request), 'meta' => (object) [], 'message' => null]);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['errors' => [['code' => 'customer_not_found', 'message' => 'The requested customer was not found.']]], 'message' => 'The requested resource was not found.'], 404);
    }
}

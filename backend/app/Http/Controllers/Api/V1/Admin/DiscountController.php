<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListDiscountsRequest;
use App\Http\Requests\Api\V1\Admin\StoreDiscountRequest;
use App\Http\Requests\Api\V1\Admin\UpdateDiscountRequest;
use App\Http\Resources\Api\V1\Admin\DiscountResource;
use App\Models\Discount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class DiscountController extends Controller
{
    public function index(ListDiscountsRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $query = Discount::query();
        if (isset($filters['search'])) {
            $search = addcslashes($filters['search'], '\\%_');
            $query->where(fn (Builder $query) => $query->where('code', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%"));
        } if (array_key_exists('is_active', $filters)) {
            $query->where('is_active', $filters['is_active']);
        } match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->oldest(), 'code_asc' => $query->orderBy('code'), 'code_desc' => $query->orderByDesc('code'), default => $query->latest()
        };
        $paginator = $query->paginate($filters['per_page'] ?? 20);

        return response()->json(['data' => DiscountResource::collection($paginator->getCollection())->resolve($request), 'meta' => ['pagination' => ['total' => $paginator->total(), 'per_page' => $paginator->perPage(), 'current_page' => $paginator->currentPage(), 'last_page' => max(1, $paginator->lastPage())]], 'message' => null]);
    }

    public function store(StoreDiscountRequest $request): JsonResponse
    {
        return $this->response($request, Discount::query()->create($this->normalize($request->validated())), 201);
    }

    public function show(Request $request, string $discount): JsonResponse
    {
        $record = Discount::query()->where('public_id', $discount)->first();

        return $record ? $this->response($request, $record) : $this->notFound();
    }

    public function update(UpdateDiscountRequest $request, string $discount): JsonResponse
    {
        $record = Discount::query()->where('public_id', $discount)->first();
        if (! $record) {
            return $this->notFound();
        } $record->update($this->normalize($request->validated()));

        return $this->response($request, $record->refresh());
    }

    public function destroy(string $discount): Response|JsonResponse
    {
        $record = Discount::query()->where('public_id', $discount)->first();
        if (! $record) {
            return $this->notFound();
        } if ($record->redemptions()->exists()) {
            return response()->json(['data' => null, 'meta' => ['errors' => [['code' => 'discount_has_redemptions', 'message' => 'Used discounts cannot be deleted. Deactivate it instead.']]], 'message' => 'The request conflicts with the current resource state.'], 409);
        } $record->delete();

        return response()->noContent();
    }

    private function normalize(array $data): array
    {
        if (isset($data['code'])) {
            $data['code'] = Str::upper($data['code']);
        } if (($data['type'] ?? null) === 'percentage') {
            $data['currency'] = null;
        }

return $data;
    }

    private function response(Request $request, Discount $discount, int $status = 200): JsonResponse
    {
        return response()->json(['data' => (new DiscountResource($discount))->resolve($request), 'meta' => (object) [], 'message' => null], $status);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['errors' => [['code' => 'discount_not_found', 'message' => 'The requested discount was not found.']]], 'message' => 'The requested resource was not found.'], 404);
    }
}

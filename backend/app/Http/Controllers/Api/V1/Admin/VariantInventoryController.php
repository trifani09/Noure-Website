<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Catalog\AdminInventoryService;
use App\Exceptions\InventoryConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\AdjustInventoryRequest;
use App\Http\Requests\Api\V1\Admin\ListInventoryMovementsRequest;
use App\Http\Resources\Api\V1\Admin\InventoryLevelResource;
use App\Http\Resources\Api\V1\Admin\InventoryMovementResource;
use App\Models\ProductVariant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VariantInventoryController extends Controller
{
    public function __construct(private readonly AdminInventoryService $inventory) {}

    public function index(Request $request, string $variant_public_id): JsonResponse
    {
        $variant = $this->variant($variant_public_id);
        if (! $variant) {
            return $this->notFound();
        }

        return response()->json(['data' => InventoryLevelResource::collection($this->inventory->levels($variant))->resolve($request), 'meta' => (object) [], 'message' => null]);
    }

    public function adjust(AdjustInventoryRequest $request, string $variant_public_id): JsonResponse
    {
        $variant = $this->variant($variant_public_id);
        if (! $variant) {
            return $this->notFound();
        }
        try {
            [$level, $movement] = $this->inventory->adjust($variant, $request->user(), $request->validated());
        } catch (InventoryConflictException $exception) {
            return $this->conflict($exception);
        }

        return response()->json(['data' => ['level' => (new InventoryLevelResource($level))->resolve($request), 'movement' => (new InventoryMovementResource($movement))->resolve($request)], 'meta' => (object) [], 'message' => null], 201);
    }

    public function movements(ListInventoryMovementsRequest $request, string $variant_public_id): JsonResponse
    {
        $variant = $this->variant($variant_public_id);
        if (! $variant) {
            return $this->notFound();
        }
        $paginator = $this->inventory->movements($variant, $request->validated());

        return response()->json(['data' => InventoryMovementResource::collection($paginator->getCollection())->resolve($request), 'meta' => ['pagination' => ['total' => $paginator->total(), 'per_page' => $paginator->perPage(), 'current_page' => $paginator->currentPage(), 'last_page' => max(1, $paginator->lastPage())]], 'message' => null]);
    }

    private function variant(string $publicId): ?ProductVariant
    {
        return ProductVariant::query()->where('public_id', $publicId)->first();
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['errors' => [['code' => 'variant_not_found', 'message' => 'The requested variant was not found.']]], 'message' => 'The requested resource was not found.'], 404);
    }

    private function conflict(InventoryConflictException $exception): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['errors' => [['code' => $exception->errorCode, 'message' => $exception->getMessage()]]], 'message' => 'The request conflicts with the current resource state.'], 409);
    }
}

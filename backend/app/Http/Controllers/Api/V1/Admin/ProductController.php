<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Catalog\AdminProductQuery;
use App\Catalog\AdminProductService;
use App\Exceptions\ProductConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListProductsRequest;
use App\Http\Requests\Api\V1\Admin\StoreProductRequest;
use App\Http\Requests\Api\V1\Admin\UpdateProductRequest;
use App\Http\Resources\Api\V1\Admin\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductController extends Controller
{
    public function __construct(private readonly AdminProductQuery $products, private readonly AdminProductService $service) {}

    public function index(ListProductsRequest $request): JsonResponse
    {
        $paginator = $this->products->paginate($request->validated());

        return response()->json(['data' => ProductResource::collection($paginator->getCollection())->resolve($request), 'meta' => ['pagination' => ['total' => $paginator->total(), 'per_page' => $paginator->perPage(), 'current_page' => $paginator->currentPage(), 'last_page' => max(1, $paginator->lastPage())]], 'message' => null]);
    }

    public function store(StoreProductRequest $request): JsonResponse
    {
        try {
            $product = $this->service->create($request->validated());
        } catch (ProductConflictException $exception) {
            return $this->conflict($exception);
        }

        return $this->resource($request, $this->products->find($product->public_id), 201);
    }

    public function show(Request $request, string $public_id): JsonResponse
    {
        $product = $this->products->find($public_id);

        return $product ? $this->resource($request, $product) : $this->notFound();
    }

    public function update(UpdateProductRequest $request, string $public_id): JsonResponse
    {
        $product = $this->products->find($public_id);
        if (! $product) {
            return $this->notFound();
        }
        try {
            $this->service->update($product, $request->validated());
        } catch (ProductConflictException $exception) {
            return $this->conflict($exception);
        }

        return $this->resource($request, $this->products->find($public_id));
    }

    public function destroy(string $public_id): Response|JsonResponse
    {
        $product = $this->products->find($public_id);
        if (! $product) {
            return $this->notFound();
        }
        $this->service->delete($product);

        return response()->noContent();
    }

    private function resource(Request $request, object $product, int $status = 200): JsonResponse
    {
        return response()->json(['data' => (new ProductResource($product))->resolve($request), 'meta' => (object) [], 'message' => null], $status);
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['errors' => [['code' => 'product_not_found', 'message' => 'The requested product was not found.']]], 'message' => 'The requested resource was not found.'], 404);
    }

    private function conflict(ProductConflictException $exception): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['errors' => [['code' => $exception->errorCode, 'message' => $exception->getMessage()]]], 'message' => 'The request conflicts with the current resource state.'], 409);
    }
}

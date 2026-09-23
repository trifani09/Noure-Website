<?php

namespace App\Http\Controllers\Api\V1;

use App\Catalog\PublicProductQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListProductsRequest;
use App\Http\Requests\Api\V1\ShowProductRequest;
use App\Http\Resources\Api\V1\PublicProductDetailResource;
use App\Http\Resources\Api\V1\PublicProductListResource;
use Illuminate\Http\JsonResponse;

class PublicProductController extends Controller
{
    public function __construct(private readonly PublicProductQuery $products) {}

    public function index(ListProductsRequest $request): JsonResponse
    {
        $filters = $request->validated();

        if (isset($filters['category']) && ! $this->products->publicCategoryExists($filters['category'])) {
            return $this->categoryNotFound();
        }

        $paginator = $this->products->paginate($filters);

        return response()->json([
            'data' => PublicProductListResource::collection($paginator->getCollection())->resolve($request),
            'meta' => ['pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => max(1, $paginator->lastPage()),
            ]],
            'message' => null,
        ]);
    }

    public function show(ShowProductRequest $request, string $slug): JsonResponse
    {
        $product = $this->products->detail($slug);

        if ($product === null) {
            return $this->productNotFound();
        }

        return response()->json([
            'data' => (new PublicProductDetailResource($product))->resolve($request),
            'meta' => (object) [],
            'message' => null,
        ]);
    }

    public function filters(): JsonResponse
    {
        return response()->json([
            'data' => $this->products->filterOptions(),
            'meta' => (object) [],
            'message' => null,
        ]);
    }

    private function productNotFound(): JsonResponse
    {
        return $this->notFound('product_not_found', 'The requested product was not found.');
    }

    private function categoryNotFound(): JsonResponse
    {
        return $this->notFound('category_not_found', 'The requested category was not found.');
    }

    private function notFound(string $code, string $detail): JsonResponse
    {
        return response()->json([
            'data' => null,
            'meta' => ['errors' => [['code' => $code, 'message' => $detail]]],
            'message' => 'The requested resource was not found.',
        ], 404);
    }
}

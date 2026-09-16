<?php

namespace App\Http\Controllers\Api\V1;

use App\Catalog\PublicCategoryQuery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListCategoriesRequest;
use App\Http\Requests\Api\V1\ShowCategoryRequest;
use App\Http\Resources\Api\V1\PublicCategoryResource;
use Illuminate\Http\JsonResponse;

class PublicCategoryController extends Controller
{
    public function __construct(private readonly PublicCategoryQuery $categories) {}

    public function index(ListCategoriesRequest $request): JsonResponse
    {
        $filters = $request->validated();

        if (isset($filters['parent']) && $filters['parent'] !== 'root'
            && $this->categories->findByPublicId($filters['parent']) === null) {
            return $this->notFound();
        }

        $paginator = $this->categories->paginate($filters);

        return response()->json([
            'data' => PublicCategoryResource::collection($paginator->getCollection())->resolve($request),
            'meta' => ['pagination' => $this->pagination($paginator)],
            'message' => null,
        ]);
    }

    public function show(ShowCategoryRequest $request, string $slug): JsonResponse
    {
        $includeProductCount = $request->boolean('include_product_count', true);
        $category = $this->categories->detail($slug, $includeProductCount);

        if ($category === null) {
            return $this->notFound();
        }

        return response()->json([
            'data' => (new PublicCategoryResource($category))->resolve($request),
            'meta' => (object) [],
            'message' => null,
        ]);
    }

    /** @return array{total: int, per_page: int, current_page: int, last_page: int} */
    private function pagination(object $paginator): array
    {
        return [
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => max(1, $paginator->lastPage()),
        ];
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'data' => null,
            'meta' => ['errors' => [[
                'code' => 'category_not_found',
                'message' => 'The requested category was not found.',
            ]]],
            'message' => 'The requested resource was not found.',
        ], 404);
    }
}

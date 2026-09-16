<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Catalog\AdminCategoryQuery;
use App\Catalog\AdminCategoryService;
use App\Exceptions\CategoryConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\ListCategoriesRequest;
use App\Http\Requests\Api\V1\Admin\StoreCategoryRequest;
use App\Http\Requests\Api\V1\Admin\UpdateCategoryRequest;
use App\Http\Resources\Api\V1\Admin\CategoryResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CategoryController extends Controller
{
    public function __construct(
        private readonly AdminCategoryQuery $categories,
        private readonly AdminCategoryService $service,
    ) {}

    public function index(ListCategoriesRequest $request): JsonResponse
    {
        $filters = $request->validated();
        if (isset($filters['parent']) && $filters['parent'] !== 'root' && $this->categories->find($filters['parent']) === null) {
            return $this->notFound();
        }

        $paginator = $this->categories->paginate($filters);

        return response()->json([
            'data' => CategoryResource::collection($paginator->getCollection())->resolve($request),
            'meta' => ['pagination' => [
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => max(1, $paginator->lastPage()),
            ]],
            'message' => null,
        ]);
    }

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->service->create($request->validated());
        } catch (CategoryConflictException $exception) {
            return $this->conflict($exception);
        }

        return $this->resourceResponse($request, $this->categories->find($category->public_id), 201);
    }

    public function show(Request $request, string $public_id): JsonResponse
    {
        $category = $this->categories->find($public_id);

        return $category === null ? $this->notFound() : $this->resourceResponse($request, $category);
    }

    public function update(UpdateCategoryRequest $request, string $public_id): JsonResponse
    {
        $category = $this->categories->find($public_id);
        if ($category === null) {
            return $this->notFound();
        }

        try {
            $this->service->update($category, $request->validated());
        } catch (CategoryConflictException $exception) {
            return $this->conflict($exception);
        }

        return $this->resourceResponse($request, $this->categories->find($public_id));
    }

    public function destroy(string $public_id): Response|JsonResponse
    {
        $category = $this->categories->find($public_id);
        if ($category === null) {
            return $this->notFound();
        }

        try {
            $this->service->delete($category);
        } catch (CategoryConflictException $exception) {
            return $this->conflict($exception);
        }

        return response()->noContent();
    }

    private function resourceResponse(Request $request, object $category, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => (new CategoryResource($category))->resolve($request),
            'meta' => (object) [],
            'message' => null,
        ], $status);
    }

    private function notFound(): JsonResponse
    {
        return response()->json([
            'data' => null,
            'meta' => ['errors' => [['code' => 'category_not_found', 'message' => 'The requested category was not found.']]],
            'message' => 'The requested resource was not found.',
        ], 404);
    }

    private function conflict(CategoryConflictException $exception): JsonResponse
    {
        return response()->json([
            'data' => null,
            'meta' => ['errors' => [['code' => $exception->errorCode, 'message' => $exception->getMessage()]]],
            'message' => 'The request conflicts with the current resource state.',
        ], 409);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Catalog\AdminProductMediaService;
use App\Exceptions\ProductConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\StoreProductImageRequest;
use App\Http\Requests\Api\V1\Admin\UpdateProductImageRequest;
use App\Http\Resources\Api\V1\Admin\ProductImageResource;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class ProductImageController extends Controller
{
    public function __construct(private readonly AdminProductMediaService $media) {}

    public function index(Request $request, string $product_public_id): JsonResponse
    {
        $product = $this->product($product_public_id);
        if (! $product) {
            return $this->notFound('product');
        }

        return response()->json(['data' => ProductImageResource::collection($this->media->list($product))->resolve($request), 'meta' => (object) [], 'message' => null]);
    }

    public function store(StoreProductImageRequest $request, string $product_public_id): JsonResponse
    {
        $product = $this->product($product_public_id);
        if (! $product) {
            return $this->notFound('product');
        }
        try {
            $image = $this->media->upload($product, $request->file('image'), $request->safe()->except('image'));
        } catch (ProductConflictException $exception) {
            return $this->conflict($exception);
        }

        return $this->resource($request, $image, 201);
    }

    public function update(UpdateProductImageRequest $request, string $product_public_id, string $image_id): JsonResponse
    {
        [$product, $image] = $this->productAndImage($product_public_id, $image_id);
        if (! $product) {
            return $this->notFound('product');
        }
        if (! $image) {
            return $this->notFound('image');
        }
        try {
            $updated = $this->media->update($product, $image, $request->validated());
        } catch (ProductConflictException $exception) {
            return $this->conflict($exception);
        }

        return $this->resource($request, $updated);
    }

    public function destroy(string $product_public_id, string $image_id): Response|JsonResponse
    {
        [$product, $image] = $this->productAndImage($product_public_id, $image_id);
        if (! $product) {
            return $this->notFound('product');
        }
        if (! $image) {
            return $this->notFound('image');
        }
        $this->media->delete($product, $image);

        return response()->noContent();
    }

    private function product(string $publicId): ?Product
    {
        return Product::query()->where('public_id', $publicId)->first();
    }

    private function productAndImage(string $productPublicId, string $imagePublicId): array
    {
        $product = $this->product($productPublicId);

        return [$product, $product?->images()->where('public_id', $imagePublicId)->with('variant')->first()];
    }

    private function resource(Request $request, ProductImage $image, int $status = 200): JsonResponse
    {
        return response()->json(['data' => (new ProductImageResource($image))->resolve($request), 'meta' => (object) [], 'message' => null], $status);
    }

    private function notFound(string $resource): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['errors' => [['code' => $resource.'_not_found', 'message' => 'The requested '.$resource.' was not found.']]], 'message' => 'The requested resource was not found.'], 404);
    }

    private function conflict(ProductConflictException $exception): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['errors' => [['code' => $exception->errorCode, 'message' => $exception->getMessage()]]], 'message' => 'The request conflicts with the current resource state.'], 409);
    }
}

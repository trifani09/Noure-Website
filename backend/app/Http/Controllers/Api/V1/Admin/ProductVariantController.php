<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Catalog\AdminProductVariantService;
use App\Exceptions\ProductConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Admin\GenerateProductVariantsRequest;
use App\Http\Requests\Api\V1\Admin\StoreProductOptionRequest;
use App\Http\Requests\Api\V1\Admin\StoreProductOptionValueRequest;
use App\Http\Requests\Api\V1\Admin\UpdateProductVariantRequest;
use App\Http\Resources\Api\V1\Admin\ProductVariantResource;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function __construct(private readonly AdminProductVariantService $service) {}

    public function storeOption(StoreProductOptionRequest $request, string $product_public_id): JsonResponse
    {
        $product = $this->product($product_public_id);
        if (! $product) {
            return $this->notFound('product');
        }
        try {
            $option = $this->service->addOption($product, $request->validated());
        } catch (ProductConflictException $exception) {
            return $this->conflict($exception);
        }

        return response()->json(['data' => ['name' => $option->name, 'code' => $option->code, 'sort_order' => $option->sort_order, 'values' => $option->values->map(fn ($value) => ['label' => $value->label, 'code' => $value->code, 'swatch_value' => $value->swatch_value, 'sort_order' => $value->sort_order])], 'meta' => (object) [], 'message' => null], 201);
    }

    public function storeOptionValue(StoreProductOptionValueRequest $request, string $product_public_id, string $option_code): JsonResponse
    {
        $product = $this->product($product_public_id);
        if (! $product) {
            return $this->notFound('product');
        }
        $option = $product->options()->where('code', $option_code)->first();
        if (! $option) {
            return $this->notFound('option');
        }
        try {
            $value = $this->service->addOptionValue($option, $request->validated());
        } catch (ProductConflictException $exception) {
            return $this->conflict($exception);
        }

        return response()->json(['data' => ['label' => $value->label, 'code' => $value->code, 'swatch_value' => $value->swatch_value, 'sort_order' => $value->sort_order], 'meta' => (object) [], 'message' => null], 201);
    }

    public function generate(GenerateProductVariantsRequest $request, string $product_public_id): JsonResponse
    {
        $product = $this->product($product_public_id);
        if (! $product) {
            return $this->notFound('product');
        }
        try {
            $result = $this->service->generate($product, $request->validated());
        } catch (ProductConflictException $exception) {
            return $this->conflict($exception);
        }
        $status = $result['created']->isEmpty() ? 200 : 201;

        return response()->json(['data' => ['created' => ProductVariantResource::collection($result['created'])->resolve($request), 'existing' => ProductVariantResource::collection($result['existing'])->resolve($request)], 'meta' => (object) [], 'message' => null], $status);
    }

    public function update(UpdateProductVariantRequest $request, string $product_public_id, string $variant_public_id): JsonResponse
    {
        [$product, $variant] = $this->productAndVariant($product_public_id, $variant_public_id);
        if (! $product) {
            return $this->notFound('product');
        }
        if (! $variant) {
            return $this->notFound('variant');
        }
        try {
            $updated = $this->service->update($product, $variant, $request->validated());
        } catch (ProductConflictException $exception) {
            return $this->conflict($exception);
        }

        return $this->variantResponse($request, $updated);
    }

    public function setDefault(Request $request, string $product_public_id, string $variant_public_id): JsonResponse
    {
        [$product, $variant] = $this->productAndVariant($product_public_id, $variant_public_id);
        if (! $product) {
            return $this->notFound('product');
        }
        if (! $variant) {
            return $this->notFound('variant');
        }
        try {
            $updated = $this->service->setDefault($product, $variant);
        } catch (ProductConflictException $exception) {
            return $this->conflict($exception);
        }

        return $this->variantResponse($request, $updated);
    }

    private function product(string $publicId): ?Product
    {
        return Product::query()->where('public_id', $publicId)->first();
    }

    private function productAndVariant(string $productPublicId, string $variantPublicId): array
    {
        $product = $this->product($productPublicId);

        return [$product, $product?->variants()->where('public_id', $variantPublicId)->first()];
    }

    private function variantResponse(Request $request, object $variant): JsonResponse
    {
        return response()->json(['data' => (new ProductVariantResource($variant))->resolve($request), 'meta' => (object) [], 'message' => null]);
    }

    private function notFound(string $resource): JsonResponse
    {
        return response()->json(['data' => null, 'meta' => ['errors' => [['code' => $resource.'_not_found', 'message' => 'The requested '.$resource.' was not found.']]], 'message' => 'The requested resource was not found.'], 404);
    }

    private function conflict(ProductConflictException $exception): JsonResponse
    {
        $error = ['code' => $exception->errorCode, 'message' => $exception->getMessage()];
        if ($exception->errorCode === 'sku_conflict') {
            $error['field'] = 'sku';
        }

        return response()->json(['data' => null, 'meta' => ['errors' => [$error]], 'message' => 'The request conflicts with the current resource state.'], 409);
    }
}

<?php

use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\BannerController;
use App\Http\Controllers\Api\V1\Admin\CategoryController;
use App\Http\Controllers\Api\V1\Admin\HomepageSectionController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\ProductImageController;
use App\Http\Controllers\Api\V1\Admin\ProductImportController;
use App\Http\Controllers\Api\V1\Admin\ProductVariantController;
use App\Http\Controllers\Api\V1\Admin\VariantInventoryController;
use App\Http\Controllers\Api\V1\HomepageController;
use App\Http\Controllers\Api\V1\PublicCategoryController;
use App\Http\Controllers\Api\V1\PublicProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('categories', [PublicCategoryController::class, 'index'])->name('api.v1.categories.index');
    Route::get('categories/{slug}', [PublicCategoryController::class, 'show'])->name('api.v1.categories.show');
    Route::get('products', [PublicProductController::class, 'index'])->name('api.v1.products.index');
    Route::get('products/{slug}', [PublicProductController::class, 'show'])->name('api.v1.products.show');
    Route::get('homepage', HomepageController::class)->name('api.v1.homepage');

    Route::prefix('admin/auth')->name('api.v1.admin.auth.')->group(function (): void {
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        });
    });

    Route::middleware('auth:sanctum')->prefix('admin')->name('api.v1.admin.')->group(function (): void {
        Route::apiResource('banners', BannerController::class)->except(['show'])->parameters(['banners' => 'public_id']);
        Route::apiResource('homepage-sections', HomepageSectionController::class)->except(['show'])->parameters(['homepage-sections' => 'public_id']);
        Route::post('product-imports/preview', [ProductImportController::class, 'preview'])->name('product-imports.preview');
        Route::post('product-imports', [ProductImportController::class, 'store'])->name('product-imports.store');
        Route::get('product-imports/templates/{type}', [ProductImportController::class, 'template'])->name('product-imports.template');
        Route::apiResource('categories', CategoryController::class)->parameters(['categories' => 'public_id']);
        Route::get('products/{product_public_id}/images', [ProductImageController::class, 'index'])->name('products.images.index');
        Route::post('products/{product_public_id}/images', [ProductImageController::class, 'store'])->name('products.images.store');
        Route::put('products/{product_public_id}/images/{image_id}', [ProductImageController::class, 'update'])->name('products.images.update');
        Route::delete('products/{product_public_id}/images/{image_id}', [ProductImageController::class, 'destroy'])->name('products.images.destroy');
        Route::post('products/{product_public_id}/options', [ProductVariantController::class, 'storeOption'])->name('products.options.store');
        Route::post('products/{product_public_id}/options/{option_code}/values', [ProductVariantController::class, 'storeOptionValue'])->name('products.options.values.store');
        Route::post('products/{product_public_id}/variants/generate', [ProductVariantController::class, 'generate'])->name('products.variants.generate');
        Route::put('products/{product_public_id}/variants/{variant_public_id}', [ProductVariantController::class, 'update'])->name('products.variants.update');
        Route::put('products/{product_public_id}/variants/{variant_public_id}/default', [ProductVariantController::class, 'setDefault'])->name('products.variants.default');
        Route::apiResource('products', ProductController::class)->parameters(['products' => 'public_id']);
        Route::get('variants/{variant_public_id}/inventory', [VariantInventoryController::class, 'index'])->name('variants.inventory.index');
        Route::post('variants/{variant_public_id}/inventory/adjustments', [VariantInventoryController::class, 'adjust'])->name('variants.inventory.adjustments.store');
        Route::get('variants/{variant_public_id}/inventory/movements', [VariantInventoryController::class, 'movements'])->name('variants.inventory.movements.index');
    });
});

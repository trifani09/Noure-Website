<?php

use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\PublicCategoryController;
use App\Http\Controllers\Api\V1\PublicProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('categories', [PublicCategoryController::class, 'index'])->name('api.v1.categories.index');
    Route::get('categories/{slug}', [PublicCategoryController::class, 'show'])->name('api.v1.categories.show');
    Route::get('products', [PublicProductController::class, 'index'])->name('api.v1.products.index');
    Route::get('products/{slug}', [PublicProductController::class, 'show'])->name('api.v1.products.show');

    Route::prefix('admin/auth')->name('api.v1.admin.auth.')->group(function (): void {
        Route::post('login', [AuthController::class, 'login'])->name('login');
        Route::middleware('auth:sanctum')->group(function (): void {
            Route::get('me', [AuthController::class, 'me'])->name('me');
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
        });
    });
});

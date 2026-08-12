<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Category\CategoryController;
use App\Http\Controllers\Api\Product\ProductController;
use App\Http\Controllers\Api\ProductVariant\ProductVariantController;
use App\Http\Controllers\Api\VariantGroup\VariantGroupController;
use App\Http\Controllers\Api\VariantOption\VariantOptionController;
use App\Http\Controllers\Api\PageContent\PageContentController;
use App\Http\Controllers\Api\PageSection\PageSectionController;
use App\Http\Controllers\Api\SectionItem\SectionItemController;
use App\Http\Controllers\Api\File\FileController;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/logout-device', [AuthController::class, 'logoutFromDevice']);
        Route::post('/logout-all', [AuthController::class, 'logoutFromAllDevices']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::match(['put', 'post'], '/profile', [AuthController::class, 'updateProfile']);
    });
});

Route::middleware('auth:sanctum')->group(function () {
    Route::prefix('categories')->group(function () {
        Route::get('/', [CategoryController::class, 'index']);
        Route::post('/', [CategoryController::class, 'store']);
        Route::get('/{id}', [CategoryController::class, 'show']);
        Route::put('/{id}', [CategoryController::class, 'update']);
        Route::patch('/{id}', [CategoryController::class, 'update']);
        Route::delete('/{id}', [CategoryController::class, 'destroy']);
    });

    Route::prefix('products')->group(function () {
        Route::get('/', [ProductController::class, 'index']);
        Route::post('/', [ProductController::class, 'store']);
        Route::get('/{id}', [ProductController::class, 'show']);
        Route::put('/{id}', [ProductController::class, 'update']);
        Route::patch('/{id}', [ProductController::class, 'update']);
        Route::patch('/{id}/variant-groups/{configurationId}', [ProductController::class, 'updateVariantGroup']);
        Route::delete('/{id}/variant-groups/{configurationId}', [ProductController::class, 'destroyVariantGroup']);
        Route::delete('/{id}', [ProductController::class, 'destroy']);
    });

    Route::prefix('variant-groups')->group(function () {
        Route::get('/', [VariantGroupController::class, 'index']);
        Route::post('/', [VariantGroupController::class, 'store']);
        Route::get('/{id}/usage', [VariantGroupController::class, 'usage']);
        Route::get('/{id}', [VariantGroupController::class, 'show']);
        Route::put('/{id}', [VariantGroupController::class, 'update']);
        Route::patch('/{id}', [VariantGroupController::class, 'update']);
        Route::delete('/{id}', [VariantGroupController::class, 'destroy']);
    });

    Route::prefix('variant-options')->group(function () {
        Route::get('/', [VariantOptionController::class, 'index']);
        Route::post('/', [VariantOptionController::class, 'store']);
        Route::get('/{id}', [VariantOptionController::class, 'show']);
        Route::put('/{id}', [VariantOptionController::class, 'update']);
        Route::patch('/{id}', [VariantOptionController::class, 'update']);
        Route::delete('/{id}', [VariantOptionController::class, 'destroy']);
    });

    Route::prefix('product-variants')->group(function () {
        Route::get('/', [ProductVariantController::class, 'index']);
        Route::post('/', [ProductVariantController::class, 'store']);
        Route::get('/{id}', [ProductVariantController::class, 'show']);
        Route::put('/{id}', [ProductVariantController::class, 'update']);
        Route::patch('/{id}', [ProductVariantController::class, 'update']);
        Route::delete('/{id}', [ProductVariantController::class, 'destroy']);
    });

    Route::prefix('page-contents')->group(function () {
        Route::get('/', [PageContentController::class, 'index']);
        Route::post('/', [PageContentController::class, 'store']);
        Route::get('/{id}', [PageContentController::class, 'show']);
        Route::put('/{id}', [PageContentController::class, 'update']);
        Route::patch('/{id}', [PageContentController::class, 'update']);
        Route::delete('/{id}', [PageContentController::class, 'destroy']);
    });

    Route::prefix('page-sections')->group(function () {
        Route::get('/', [PageSectionController::class, 'index']);
        Route::post('/', [PageSectionController::class, 'store']);
        Route::get('/{id}', [PageSectionController::class, 'show']);
        Route::put('/{id}', [PageSectionController::class, 'update']);
        Route::patch('/{id}', [PageSectionController::class, 'update']);
        Route::delete('/{id}', [PageSectionController::class, 'destroy']);
    });

    Route::prefix('section-items')->group(function () {
        Route::get('/', [SectionItemController::class, 'index']);
        Route::post('/', [SectionItemController::class, 'store']);
        Route::get('/{id}', [SectionItemController::class, 'show']);
        Route::put('/{id}', [SectionItemController::class, 'update']);
        Route::patch('/{id}', [SectionItemController::class, 'update']);
        Route::delete('/{id}', [SectionItemController::class, 'destroy']);
    });

    Route::prefix('files')->group(function () {
        Route::get('/{id}', [FileController::class, 'show']);
        Route::put('/{id}', [FileController::class, 'update']);
        Route::patch('/{id}', [FileController::class, 'update']);
        Route::post('/{id}/replace', [FileController::class, 'replace']);
        Route::delete('/{id}', [FileController::class, 'destroy']);
    });
});

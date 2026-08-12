<?php

use App\Http\Controllers\Cms\CategoryController;
use App\Http\Controllers\Cms\AuthController;
use App\Http\Controllers\Cms\DashboardController;
use App\Http\Controllers\Cms\PageContentController;
use App\Http\Controllers\Cms\PageConfigController;
use App\Http\Controllers\Cms\PageSectionController;
use App\Http\Controllers\Cms\ProductController;
use App\Http\Controllers\Cms\ProductVariantController;
use App\Http\Controllers\Cms\SectionItemController;
use App\Http\Controllers\Cms\VariantGroupController;
use App\Http\Controllers\Cms\VariantOptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

Route::prefix('cms')->name('cms.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1')->name('login.submit');

    Route::middleware(['auth', 'cms.token.valid'])->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::resource('products', ProductController::class)->only(['index', 'create', 'edit']);
        Route::resource('categories', CategoryController::class)->only(['index', 'create', 'edit']);
        Route::resource('variant-groups', VariantGroupController::class)->only(['index', 'create', 'edit']);
        Route::resource('variant-options', VariantOptionController::class)->only(['index', 'create', 'edit']);
        Route::resource('product-variants', ProductVariantController::class)->only(['index', 'create', 'edit']);
        Route::resource('page-contents', PageContentController::class)->only(['index', 'create', 'edit']);
        Route::get('page-configs', [PageConfigController::class, 'index'])->name('page-configs.index');
        Route::resource('page-sections', PageSectionController::class)->only(['index', 'create', 'edit']);
        Route::resource('section-items', SectionItemController::class)->only(['index', 'create', 'edit']);
    });
});

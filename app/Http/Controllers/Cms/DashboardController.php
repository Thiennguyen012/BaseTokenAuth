<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Categories\Category;
use App\Models\PageContents\PageContent;
use App\Models\Products\Product;
use App\Models\ProductVariants\ProductVariant;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('cms.dashboard', [
            'stats' => [
                ['label' => 'Sản phẩm', 'value' => Product::query()->count()],
                ['label' => 'Danh mục', 'value' => Category::query()->count()],
                ['label' => 'Biến thể', 'value' => ProductVariant::query()->count()],
                ['label' => 'Trang nội dung', 'value' => PageContent::query()->count()],
            ],
            'breadcrumbs' => [
                ['label' => 'Tổng quan', 'url' => null],
            ],
        ]);
    }
}

<?php

namespace App\Http\Controllers\Cms;

use App\Models\Products\Product;
use App\Models\ProductVariants\ProductVariant;
use Illuminate\View\View;

class ProductVariantController extends ModuleController
{
    protected string $module = 'product-variants';

    public function forProduct(string $product): View
    {
        $productModel = Product::query()
            ->with(['variantGroupConfigurations.group', 'variantGroupConfigurations.options'])
            ->findOrFail($product);
        $config = config('cms.modules.product-variants');
        $config['title'] = 'Biến thể của ' . $productModel->product_name;
        $config['description'] = 'Quản lý SKU, tổ hợp, giá, tồn kho và hình ảnh của sản phẩm này.';
        $config['filters'] = $productModel->variantGroupConfigurations
            ->map(fn ($configuration) => [
                'name' => 'variant_group_' . $configuration->id,
                'query_name' => 'option_ids[]',
                'label' => $configuration->group?->group_name ?: 'Nhóm biến thể',
                'value' => 'id',
                'text' => 'label',
                'items' => $configuration->options
                    ->where('is_active', true)
                    ->map(fn ($option) => ['id' => $option->id, 'label' => $option->option_name])
                    ->values(),
            ])
            ->filter(fn ($filter) => $filter['items']->isNotEmpty())
            ->values()
            ->all();

        return view('cms.product-variants.index', [
            'module' => $this->module,
            'config' => $config,
            'fixedParams' => ['product_id' => $productModel->id],
            'createUrl' => route('cms.product-variants.create', ['product_id' => $productModel->id]),
            'backUrl' => route('cms.products.index'),
            'backLabel' => 'Quay lại danh sách sản phẩm',
            'breadcrumbs' => [
                ['label' => 'Tổng quan', 'url' => route('cms.dashboard')],
                ['label' => 'Sản phẩm', 'url' => route('cms.products.index')],
                ['label' => $productModel->product_name, 'url' => null],
            ],
        ]);
    }

    public function create(): View
    {
        $productId = request()->integer('product_id');
        $product = $productId ? Product::query()->findOrFail($productId) : null;

        return view('cms.product-variants.create', $this->viewData([
            'indexUrl' => $productId
                ? route('cms.products.variants.index', $productId)
                : route('cms.products.index'),
            'breadcrumbs' => $product
                ? $this->productBreadcrumbs($product, 'Thêm mới')
                : [
                    ['label' => 'Tổng quan', 'url' => route('cms.dashboard')],
                    ['label' => 'Sản phẩm', 'url' => route('cms.products.index')],
                    ['label' => 'Thêm mới biến thể', 'url' => null],
                ],
        ]));
    }

    public function edit(string $id): View
    {
        $variant = ProductVariant::query()->findOrFail($id);

        return view('cms.product-variants.edit', $this->viewData([
            'recordId' => $id,
            'indexUrl' => route('cms.products.variants.index', $variant->product_id),
            'breadcrumbs' => $this->productBreadcrumbs($variant->product, 'Chỉnh sửa'),
        ]));
    }

    private function productBreadcrumbs(Product $product, string $current): array
    {
        return [
            ['label' => 'Tổng quan', 'url' => route('cms.dashboard')],
            ['label' => 'Sản phẩm', 'url' => route('cms.products.index')],
            [
                'label' => $product->product_name,
                'url' => route('cms.products.variants.index', $product->id),
            ],
            ['label' => $current, 'url' => null],
        ];
    }
}

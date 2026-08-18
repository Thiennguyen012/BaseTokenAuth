<?php

namespace Tests\Feature;

use App\Models\Categories\Category;
use App\Models\Products\Product;
use App\Services\Product\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_can_be_filtered_by_category(): void
    {
        $category = Category::query()->create(['category_name' => 'Ống nhựa']);
        $matched = Product::query()->create(['product_name' => 'Ống PVC']);
        Product::query()->create(['product_name' => 'Áo thun']);
        $matched->categories()->attach($category->id, ['sort_order' => 1]);

        $products = app(ProductService::class)->paginate(10, '', [$category->id]);

        $this->assertCount(1, $products->items());
        $this->assertSame($matched->id, $products->items()[0]->id);
    }

    public function test_products_must_belong_to_all_selected_categories(): void
    {
        $first = Category::query()->create(['category_name' => 'Ống']);
        $second = Category::query()->create(['category_name' => 'Phụ kiện']);
        $third = Category::query()->create(['category_name' => 'Khác']);
        $one = Product::query()->create(['product_name' => 'Khớp cả hai']);
        $two = Product::query()->create(['product_name' => 'Chỉ khớp một']);
        Product::query()->create(['product_name' => 'Không khớp'])->categories()->attach($third->id, ['sort_order' => 1]);
        $one->categories()->attach($first->id, ['sort_order' => 1]);
        $one->categories()->attach($second->id, ['sort_order' => 2]);
        $two->categories()->attach($second->id, ['sort_order' => 1]);

        $products = app(ProductService::class)->paginate(10, '', [$first->id, $second->id]);

        $this->assertCount(1, $products->items());
        $this->assertSame($one->id, $products->items()[0]->id);
    }

    public function test_products_can_be_sorted_by_name_and_lowest_active_variant_price(): void
    {
        $alpha = Product::query()->create(['product_name' => 'Alpha']);
        $beta = Product::query()->create(['product_name' => 'Beta']);
        $withoutPrice = Product::query()->create(['product_name' => 'No price']);

        $alpha->variants()->create(['sku' => 'ALPHA-200', 'combination_key' => 'alpha-200', 'price' => 200, 'is_active' => true]);
        $alpha->variants()->create(['sku' => 'ALPHA-050-INACTIVE', 'combination_key' => 'alpha-050', 'price' => 50, 'is_active' => false]);
        $beta->variants()->create(['sku' => 'BETA-100', 'combination_key' => 'beta-100', 'price' => 100, 'is_active' => true]);

        $service = app(ProductService::class);

        $this->assertSame(['Beta', 'Alpha', 'No price'], collect($service->paginate(10, '', [], 'price_asc')->items())->pluck('product_name')->all());
        $this->assertSame(['Alpha', 'Beta', 'No price'], collect($service->paginate(10, '', [], 'price_desc')->items())->pluck('product_name')->all());
        $this->assertSame(['Alpha', 'Beta', 'No price'], collect($service->paginate(10, '', [], 'name_asc')->items())->pluck('product_name')->all());
        $this->assertSame(['No price', 'Beta', 'Alpha'], collect($service->paginate(10, '', [], 'name_desc')->items())->pluck('product_name')->all());
    }

    public function test_products_can_be_filtered_by_featured_status(): void
    {
        Product::query()->create(['product_name' => 'Featured', 'is_featured' => true]);
        Product::query()->create(['product_name' => 'Normal', 'is_featured' => false]);

        $featured = app(ProductService::class)->paginate(10, '', [], 'latest', true);
        $normal = app(ProductService::class)->paginate(10, '', [], 'latest', false);

        $this->assertSame(['Featured'], collect($featured->items())->pluck('product_name')->all());
        $this->assertSame(['Normal'], collect($normal->items())->pluck('product_name')->all());
    }
}

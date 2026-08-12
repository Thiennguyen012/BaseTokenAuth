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
}

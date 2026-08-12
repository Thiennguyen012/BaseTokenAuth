<?php

namespace Tests\Feature;

use App\Models\Categories\Category;
use App\Services\Product\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCategoryRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_sync_multiple_categories_with_order(): void
    {
        $first = Category::query()->create(['category_name' => 'Ống nhựa']);
        $second = Category::query()->create(['category_name' => 'Phụ kiện']);

        $product = app(ProductService::class)->create([
            'product_name' => 'Ống PVC',
            'category_ids' => [$second->id, $first->id],
        ]);

        $this->assertCount(2, $product->categories);
        $this->assertSame(1, $product->categories[0]->pivot->sort_order);

        $updated = app(ProductService::class)->update($product, ['category_ids' => [$first->id]]);
        $this->assertCount(1, $updated->categories);
        $this->assertSame($first->id, $updated->categories->first()->id);
    }
}

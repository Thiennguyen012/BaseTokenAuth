<?php

namespace Tests\Feature;

use App\Models\Products\Product;
use App\Services\Product\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSlugTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_generates_unique_product_slugs(): void
    {
        $service = app(ProductService::class);

        $first = $service->create(['product_name' => 'Áo Thun Basic']);
        $second = $service->create(['product_name' => 'Áo Thun Basic']);

        $this->assertSame('ao-thun-basic', $first->slug);
        $this->assertSame('ao-thun-basic-2', $second->slug);
    }

    public function test_service_regenerates_slug_when_product_name_changes(): void
    {
        $service = app(ProductService::class);
        $existing = $service->create(['product_name' => 'Ống Nhựa PVC']);
        $product = $service->create(['product_name' => 'Sản phẩm cũ']);

        $updated = $service->update($product, ['product_name' => 'Ống Nhựa PVC']);

        $this->assertSame('ong-nhua-pvc-2', $updated->slug);
        $this->assertSame('ong-nhua-pvc', $existing->slug);
    }

    public function test_public_product_detail_accepts_slug(): void
    {
        $product = Product::query()->create([
            'product_name' => 'Ống nhựa PVC',
            'slug' => 'ong-nhua-pvc',
            'is_active' => true,
        ]);

        $this->getJson('/api/products/ong-nhua-pvc')
            ->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.slug', 'ong-nhua-pvc');
    }

    public function test_service_auto_generates_product_sku_from_name_initials_and_id(): void
    {
        $service = app(ProductService::class);

        $first = $service->create(['product_name' => 'Áo thun tay dài']);
        $second = $service->create(['product_name' => 'Hũ nắp vặn']);

        $this->assertSame('ATTD-' . str_pad((string) $first->id, 3, '0', STR_PAD_LEFT), $first->sku);
        $this->assertSame('HNV-' . str_pad((string) $second->id, 3, '0', STR_PAD_LEFT), $second->sku);
    }

    public function test_service_respects_custom_sku_when_provided(): void
    {
        $service = app(ProductService::class);

        $product = $service->create([
            'product_name' => 'Áo thun tay dài',
            'sku' => 'CUSTOM-SKU-999',
        ]);

        $this->assertSame('CUSTOM-SKU-999', $product->sku);
    }

    public function test_deleting_a_product_permanently_releases_its_sku(): void
    {
        $service = app(ProductService::class);
        $product = $service->create([
            'product_name' => 'San pham cu',
            'sku' => 'REUSABLE-SKU',
        ]);

        $service->delete($product);

        $this->assertDatabaseMissing('products', ['id' => $product->id]);

        $replacement = $service->create([
            'product_name' => 'San pham moi',
            'sku' => 'REUSABLE-SKU',
        ]);

        $this->assertSame('REUSABLE-SKU', $replacement->sku);
    }
}

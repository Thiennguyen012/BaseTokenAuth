<?php

namespace Tests\Feature;

use App\Models\PageContents\PageContent;
use App\Models\Products\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLandingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_landing_endpoints_do_not_require_authentication(): void
    {
        $this->getJson('/api/products')->assertOk();
        $this->getJson('/api/categories')->assertOk();
        $this->getJson('/api/page-contents')->assertOk();
        $this->getJson('/api/page-configs')->assertOk();
    }

    public function test_public_products_only_return_active_products(): void
    {
        $active = Product::query()->create(['product_name' => 'Công khai', 'is_active' => true]);
        Product::query()->create(['product_name' => 'Đang ẩn', 'is_active' => false]);

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $active->id);

        $this->getJson('/api/products/2')->assertNotFound();
    }

    public function test_public_page_content_can_be_read_by_slug(): void
    {
        PageContent::query()->create(['slug' => 'trang-chu', 'title' => 'Trang chủ']);

        $this->getJson('/api/page-contents/trang-chu')
            ->assertOk()
            ->assertJsonPath('data.slug', 'trang-chu');
    }

    public function test_admin_crud_is_not_exposed_under_public_prefix(): void
    {
        $this->postJson('/api/products', ['product_name' => 'Không được tạo'])->assertMethodNotAllowed();
        $this->deleteJson('/api/products/1')->assertMethodNotAllowed();
    }
}

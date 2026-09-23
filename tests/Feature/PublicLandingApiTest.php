<?php

namespace Tests\Feature;

use App\Models\PageContents\PageContent;
use App\Models\Products\Product;
use App\Models\Products\ProductVariantGroup;
use App\Models\Tags\Tag;
use App\Models\Variants\VariantGroup;
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

    public function test_public_products_can_be_filtered_by_tag_and_price_range(): void
    {
        $sale = Tag::query()->create(['name' => 'Khuyến mãi', 'slug' => 'khuyen-mai']);
        $matched = Product::query()->create(['product_name' => 'Phù hợp', 'is_active' => true]);
        $wrongTag = Product::query()->create(['product_name' => 'Sai tag', 'is_active' => true]);
        $wrongPrice = Product::query()->create(['product_name' => 'Sai giá', 'is_active' => true]);

        $matched->tags()->attach($sale);
        $wrongPrice->tags()->attach($sale);
        $matched->variants()->create(['sku' => 'MATCHED-PUBLIC', 'combination_key' => 'matched', 'price' => 150, 'is_active' => true]);
        $wrongTag->variants()->create(['sku' => 'WRONG-TAG', 'combination_key' => 'wrong-tag', 'price' => 150, 'is_active' => true]);
        $wrongPrice->variants()->create(['sku' => 'WRONG-PRICE', 'combination_key' => 'wrong-price', 'price' => 250, 'is_active' => true]);

        $this->getJson('/api/products?tag_slugs[]=khuyen-mai&min_price=100&max_price=200')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $matched->id);
    }

    public function test_public_product_price_range_is_validated(): void
    {
        $this->getJson('/api/products?min_price=100')->assertOk();
        $this->getJson('/api/products?max_price=200')->assertOk();

        $this->getJson('/api/products?min_price=200&max_price=100')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['max_price']);
    }

    public function test_public_variant_lookup_returns_variant_images_or_falls_back_to_product_images(): void
    {
        $product = Product::query()->create(['product_name' => 'Bình tổ ong', 'is_active' => true]);
        $product->files()->create([
            'title' => 'Ảnh sản phẩm',
            'file_name' => 'product.jpg',
            'path' => 'products/product.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'type' => 'image',
        ]);

        $optionIds = collect(['capacity', 'size', 'color'])->map(function (string $code) use ($product): int {
            $group = VariantGroup::query()->create([
                'group_code' => $code,
                'group_name' => ucfirst($code),
            ]);
            $configuration = ProductVariantGroup::query()->create([
                'product_id' => $product->id,
                'variant_group_id' => $group->id,
                'is_required' => true,
            ]);

            return $configuration->options()->create([
                'option_code' => $code.'-option',
                'option_name' => ucfirst($code).' option',
            ])->id;
        })->sort()->values();

        $variant = $product->variants()->create([
            'sku' => 'BINH-233-M-BLUE',
            'combination_key' => hash('sha256', $optionIds->implode(':')),
            'is_active' => true,
        ]);
        $variant->options()->attach($optionIds->all());
        $query = $optionIds->map(fn (int $id) => 'option_ids[]='.$id)->implode('&');

        $incompleteQuery = $optionIds->take(2)->map(fn (int $id) => 'option_ids[]='.$id)->implode('&');
        $this->getJson("/api/products/{$product->id}/variant?{$incompleteQuery}")
            ->assertNotFound();

        $this->getJson("/api/products/{$product->id}/variant?{$query}")
            ->assertOk()
            ->assertJsonPath('data.id', $variant->id)
            ->assertJsonPath('data.images.0.path', 'products/product.jpg')
            ->assertJsonPath('data.image_source', 'product')
            ->assertJsonPath('data.uses_product_images', true);

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.variants.0.images.0.path', 'products/product.jpg')
            ->assertJsonPath('data.variants.0.image_source', 'product');

        $variant->files()->create([
            'title' => 'Ảnh biến thể',
            'file_name' => 'variant.jpg',
            'path' => 'product-variants/variant.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'type' => 'image',
        ]);

        $this->getJson("/api/products/{$product->id}/variant?{$query}")
            ->assertOk()
            ->assertJsonPath('data.images.0.path', 'product-variants/variant.jpg')
            ->assertJsonPath('data.image_source', 'variant')
            ->assertJsonPath('data.uses_product_images', false);

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonCount(2, 'data.images')
            ->assertJsonPath('data.images.0.path', 'products/product.jpg')
            ->assertJsonPath('data.images.1.path', 'product-variants/variant.jpg')
            ->assertJsonPath('data.first_image.path', 'products/product.jpg');
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

<?php

namespace Tests\Feature;

use App\Models\Users\User;
use App\Models\Products\Product;
use App\Models\ProductVariants\ProductVariant;
use App\Models\Products\ProductVariantGroup;
use App\Models\Variants\VariantGroup;
use App\Services\User\UserAuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CmsBladeStructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_cms_login(): void
    {
        $this->get('/cms')->assertRedirect('/cms/login');
        $this->get('/cms/login')->assertOk()->assertSee('Đăng nhập CMS');
    }

    public function test_user_can_login_to_cms_with_a_server_side_blade_form(): void
    {
        User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->post('/cms/login', [
            'email' => 'admin@example.com',
            'password' => 'password123',
        ])->assertRedirect('/cms');

        $this->assertAuthenticated();
        $this->assertNotEmpty(session('cms_access_token'));
        $this->assertGreaterThan(now()->timestamp, session('cms_access_token_expires_at'));
    }

    public function test_stale_web_session_without_api_token_is_reset_on_login_page(): void
    {
        $user = User::query()->create([
            'name' => 'Admin',
            'email' => 'stale@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user)->get('/cms/login')->assertOk()->assertSee('Đăng nhập CMS');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_render_module_pages(): void
    {
        $user = User::query()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user)
            ->withSession(['cms_access_token' => 'test-token'])
            ->get('/cms/products')
            ->assertOk()
            ->assertSee('Quản lý sản phẩm');

        $this->actingAs($user)
            ->withSession(['cms_access_token' => 'test-token'])
            ->get('/cms/customer-contacts')
            ->assertOk()
            ->assertSee('Khách hàng liên hệ');

        $this->actingAs($user)
            ->withSession(['cms_access_token' => 'test-token'])
            ->get('/cms/customer-contacts/create')
            ->assertOk()
            ->assertSee('Nội dung cần tư vấn');

        $this->actingAs($user)->get('/cms/page-sections/create')->assertOk()->assertSee('Thêm bố cục');
        $this->actingAs($user)->get('/cms/section-items/1/edit')->assertOk()->assertSee('Chỉnh sửa nội dung section', false);
    }

    public function test_authenticated_user_can_render_singleton_page_config_form(): void
    {
        $user = User::query()->create([
            'name' => 'Config Admin',
            'email' => 'config@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user)
            ->withSession(['cms_access_token' => 'test-token'])
            ->get('/cms/page-configs')
            ->assertOk()
            ->assertSee('page-configs');
    }

    public function test_authenticated_user_can_render_product_variant_edit_form(): void
    {
        $user = User::query()->create([
            'name' => 'Variant Admin',
            'email' => 'variant-admin@example.com',
            'password' => Hash::make('password123'),
        ]);
        $product = Product::query()->create(['product_name' => 'Sản phẩm kiểm thử']);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'TEST-VARIANT',
            'combination_key' => hash('sha256', 'test-variant'),
        ]);

        $this->actingAs($user)
            ->withSession(['cms_access_token' => 'test-token'])
            ->get(route('cms.product-variants.edit', $variant))
            ->assertOk()
            ->assertSee('product-variants');
    }

    public function test_product_variant_form_uses_searchable_product_picker(): void
    {
        $field = collect(config('cms.modules.product-variants.fields'))
            ->firstWhere('name', 'product_id');

        $this->assertSame('searchable_select_api', $field['type']);
        $this->assertSame('Tìm theo tên hoặc SKU', $field['placeholder']);
    }

    public function test_product_variants_are_managed_from_their_product_page(): void
    {
        $user = User::query()->create([
            'name' => 'Product Admin',
            'email' => 'product-variants@example.com',
            'password' => Hash::make('password123'),
        ]);
        $product = Product::query()->create(['product_name' => 'Ống nhựa PVC']);
        foreach (['capacity' => 'Dung tích', 'size' => 'Kích cỡ', 'color' => 'Màu sắc'] as $code => $name) {
            $group = VariantGroup::query()->create(['group_code' => $code, 'group_name' => $name]);
            $configuration = ProductVariantGroup::query()->create([
                'product_id' => $product->id,
                'variant_group_id' => $group->id,
            ]);
            $configuration->options()->create([
                'option_code' => $code . '-1',
                'option_name' => $name . ' 1',
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($user)
            ->withSession(['cms_access_token' => 'test-token'])
            ->get(route('cms.products.variants.index', $product))
            ->assertOk()
            ->assertSee('Biến thể của Ống nhựa PVC')
            ->assertSee('Quay lại danh sách sản phẩm')
            ->assertSee('data-fixed-params', false)
            ->assertSee('product_id', false);
        $this->assertSame(3, substr_count($response->getContent(), 'data-filter-query-name="option_ids[]"'));

        $this->get(route('cms.product-variants.create', ['product_id' => $product->id]))
            ->assertOk()
            ->assertSeeInOrder(['Tổng quan', 'Sản phẩm', 'Ống nhựa PVC', 'Thêm mới']);

        $this->get('/cms/product-variants')->assertRedirect('/cms/products');
    }

    public function test_standalone_variant_group_page_redirects_to_products(): void
    {
        $user = User::query()->create([
            'name' => 'Group Admin',
            'email' => 'group-admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user)
            ->withSession(['cms_access_token' => 'test-token'])
            ->get('/cms/variant-groups')
            ->assertRedirect('/cms/products');
    }

    public function test_standalone_variant_option_page_redirects_to_products(): void
    {
        $user = User::query()->create([
            'name' => 'Option Admin',
            'email' => 'option-admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user)
            ->withSession(['cms_access_token' => 'test-token'])
            ->get('/cms/variant-options')
            ->assertRedirect('/cms/products');
    }

    public function test_authenticated_cms_layout_exposes_csrf_token_for_api_requests(): void
    {
        $user = User::query()->create([
            'name' => 'CSRF Admin',
            'email' => 'csrf-admin@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user)
            ->withSession(['cms_access_token' => 'test-token'])
            ->get('/cms')
            ->assertOk()
            ->assertSee('meta name="csrf-token"', false);
    }

    public function test_authenticated_user_can_logout_from_cms(): void
    {
        $user = User::query()->create([
            'name' => 'Admin Logout',
            'email' => 'logout@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user)
            ->withSession(['cms_access_token' => '1|token'])
            ->post('/cms/logout')
            ->assertRedirect('/cms/login');

        $this->assertGuest();
        $this->assertNull(session('cms_access_token'));
    }

    public function test_cms_can_refresh_access_token_without_logging_in_again(): void
    {
        $user = User::query()->create([
            'name' => 'Refresh Admin',
            'email' => 'refresh@example.com',
            'password' => Hash::make('password123'),
        ]);
        $tokens = app(UserAuthService::class)->login([
            'email' => 'refresh@example.com',
            'password' => 'password123',
        ]);

        $this->actingAs($user)
            ->withSession([
                'cms_access_token' => $tokens['access_token'],
                'cms_refresh_token' => $tokens['refresh_token'],
                'cms_access_token_expires_at' => now()->subSecond()->timestamp,
            ])
            ->post(route('cms.refresh-token'))
            ->assertOk()
            ->assertJsonPath('message', 'Đã làm mới phiên đăng nhập.')
            ->assertJsonStructure(['data' => ['access_token', 'access_token_expires_at']]);

        $this->assertNotSame($tokens['access_token'], session('cms_access_token'));
        $this->assertGreaterThan(now()->timestamp, session('cms_access_token_expires_at'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_expired_cms_access_token_is_refreshed_during_page_navigation(): void
    {
        $user = User::query()->create([
            'name' => 'Navigation Refresh Admin',
            'email' => 'navigation-refresh@example.com',
            'password' => Hash::make('password123'),
        ]);
        $tokens = app(UserAuthService::class)->login([
            'email' => 'navigation-refresh@example.com',
            'password' => 'password123',
        ]);

        $this->actingAs($user)
            ->withSession([
                'cms_access_token' => $tokens['access_token'],
                'cms_refresh_token' => $tokens['refresh_token'],
                'cms_access_token_expires_at' => now()->subSecond()->timestamp,
            ])
            ->get('/cms')
            ->assertOk();

        $this->assertNotSame($tokens['access_token'], session('cms_access_token'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_expired_cms_token_automatically_logs_user_out(): void
    {
        $user = User::query()->create([
            'name' => 'Expired Admin',
            'email' => 'expired@example.com',
            'password' => Hash::make('password123'),
        ]);

        $this->actingAs($user)
            ->withSession([
                'cms_access_token' => 'expired-token',
                'cms_access_token_expires_at' => now()->subSecond()->timestamp,
            ])
            ->get('/cms/products')
            ->assertRedirect('/cms/login');

        $this->assertGuest();
        $this->assertNull(session('cms_access_token'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\Users\User;
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

        $this->actingAs($user)->get('/cms/page-sections/create')->assertOk()->assertSee('Thêm bố cục');
        $this->actingAs($user)->get('/cms/section-items/1/edit')->assertOk()->assertSee('Chỉnh sửa nội dung section', false);
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

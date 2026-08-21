<?php

namespace Tests\Feature;

use App\Http\Requests\PageConfig\UpdatePageConfigRequest;
use App\Models\PageConfigs\PageConfig;
use App\Services\PageConfig\PageConfigService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PageConfigTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_config_is_initialized_as_a_singleton(): void
    {
        $service = app(PageConfigService::class);

        $first = $service->singleton();
        $second = $service->singleton();

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, PageConfig::query()->count());
    }

    public function test_page_config_casts_addresses_and_socials_to_arrays(): void
    {
        $config = PageConfig::query()->create([
            'company_name' => 'Công ty Nhựa',
            'slogan' => 'Chất lượng tạo niềm tin',
            'description' => 'Thông tin giới thiệu chung về công ty.',
            'addresses' => ['Kho 1', 'Kho 2'],
            'hotline' => '0915799080',
            'email' => 'contact@example.com',
            'working_hour' => '08:00 - 18:00',
            'socials' => ['tiktok' => 'https://www.tiktok.com/@company'],
            'favicon_path' => 'page-config/favicon/favicon.ico',
            'logo_path' => 'page-config/logo/logo.png',
        ])->fresh();

        $this->assertSame(['Kho 1', 'Kho 2'], $config->addresses);
        $this->assertSame('Chất lượng tạo niềm tin', $config->slogan);
        $this->assertSame('Thông tin giới thiệu chung về công ty.', $config->description);
        $this->assertSame('0915799080', $config->hotline);
        $this->assertSame('contact@example.com', $config->email);
        $this->assertSame('https://www.tiktok.com/@company', $config->socials['tiktok']);
    }

    public function test_page_config_request_validates_nested_values(): void
    {
        $request = new UpdatePageConfigRequest();

        $valid = Validator::make([
            'company_name' => 'Công ty Nhựa',
            'slogan' => 'Chất lượng tạo niềm tin',
            'description' => 'Thông tin giới thiệu chung về công ty.',
            'addresses' => ['Địa chỉ hợp lệ'],
            'hotline' => '0915799080',
            'email' => 'contact@example.com',
            'socials' => ['facebook' => 'https://facebook.com/company'],
        ], $request->rules());

        $invalid = Validator::make([
            'company_name' => 'Công ty Nhựa',
            'slogan' => str_repeat('a', 256),
            'addresses' => [str_repeat('a', 256)],
            'hotline' => '09abc',
            'email' => 'email-khong-hop-le',
            'socials' => ['facebook' => 'not-a-url'],
        ], $request->rules());

        $this->assertFalse($valid->fails());
        $this->assertTrue($invalid->fails());
        $this->assertArrayHasKey('slogan', $invalid->errors()->toArray());
        $this->assertArrayHasKey('addresses.0', $invalid->errors()->toArray());
        $this->assertArrayHasKey('hotline', $invalid->errors()->toArray());
        $this->assertArrayHasKey('email', $invalid->errors()->toArray());
        $this->assertArrayHasKey('socials.facebook', $invalid->errors()->toArray());
    }

    public function test_page_config_uploads_and_replaces_singleton_brand_images(): void
    {
        Storage::fake('public');
        $service = app(PageConfigService::class);
        $config = $service->singleton();

        $updated = $service->update($config, [
            'favicon' => [UploadedFile::fake()->image('favicon.png', 64, 64)],
            'logo' => [UploadedFile::fake()->image('logo.png', 300, 100)],
        ]);

        $this->assertNotNull($updated->favicon_path);
        $this->assertNotNull($updated->logo_path);
        $this->assertCount(2, $updated->files);

        $replaced = $service->update($updated, [
            'logo' => [UploadedFile::fake()->image('new-logo.png', 400, 120)],
        ]);

        $this->assertCount(2, $replaced->files);
        $this->assertSame('new-logo.png', $replaced->files->firstWhere('type', 'logo')->file_name);
    }

    public function test_page_config_api_returns_only_brand_file_paths_from_files_table(): void
    {
        Storage::fake('public');
        $service = app(PageConfigService::class);
        $config = $service->update($service->singleton(), [
            'favicon' => [UploadedFile::fake()->image('favicon.png', 64, 64)],
            'logo' => [UploadedFile::fake()->image('logo.png', 300, 100)],
        ]);
        $faviconPath = $config->files->firstWhere('type', 'favicon')->path;
        $logoPath = $config->files->firstWhere('type', 'logo')->path;

        PageConfig::query()->whereKey($config->id)->update([
            'favicon_path' => null,
            'logo_path' => null,
        ]);

        $this->getJson('/api/page-configs')
            ->assertOk()
            ->assertJsonPath('data.favicon_path', $faviconPath)
            ->assertJsonPath('data.logo_path', $logoPath)
            ->assertJsonMissingPath('data.favicon')
            ->assertJsonMissingPath('data.logo');
    }
}

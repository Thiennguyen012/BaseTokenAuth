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
            'addresses' => ['Kho 1', 'Kho 2'],
            'hotline' => '0915799080',
            'working_hour' => '08:00 - 18:00',
            'socials' => ['tiktok' => 'https://www.tiktok.com/@company'],
            'favicon_path' => 'page-config/favicon/favicon.ico',
            'logo_path' => 'page-config/logo/logo.png',
        ])->fresh();

        $this->assertSame(['Kho 1', 'Kho 2'], $config->addresses);
        $this->assertSame('0915799080', $config->hotline);
        $this->assertSame('https://www.tiktok.com/@company', $config->socials['tiktok']);
    }

    public function test_page_config_request_validates_nested_values(): void
    {
        $request = new UpdatePageConfigRequest();

        $valid = Validator::make([
            'company_name' => 'Công ty Nhựa',
            'addresses' => ['Địa chỉ hợp lệ'],
            'hotline' => '0915799080',
            'socials' => ['facebook' => 'https://facebook.com/company'],
        ], $request->rules());

        $invalid = Validator::make([
            'company_name' => 'Công ty Nhựa',
            'addresses' => [str_repeat('a', 256)],
            'hotline' => '09abc',
            'socials' => ['facebook' => 'not-a-url'],
        ], $request->rules());

        $this->assertFalse($valid->fails());
        $this->assertTrue($invalid->fails());
        $this->assertArrayHasKey('addresses.0', $invalid->errors()->toArray());
        $this->assertArrayHasKey('hotline', $invalid->errors()->toArray());
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
}

<?php

namespace Tests\Feature;

use App\Models\Products\Product;
use App\Services\File\FileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uploads_a_file_and_attaches_it_to_any_model(): void
    {
        Storage::fake('local');
        $product = Product::query()->create(['product_name' => 'T-shirt']);

        $file = app(FileService::class)->upload(
            UploadedFile::fake()->image('front.jpg', 1200, 1200),
            $product,
            [
                'title' => 'Front image',
                'type' => 'image',
                'directory' => 'products',
            ]
        );

        Storage::disk('local')->assertExists($file->path);
        $this->assertSame(Product::class, $file->model_type);
        $this->assertSame($product->id, $file->model_id);
        $this->assertSame('front.jpg', $file->file_name);
        $this->assertSame('image', $file->type);
        $this->assertTrue($product->files()->whereKey($file->id)->exists());
    }

    public function test_it_can_upload_first_and_attach_the_file_later(): void
    {
        Storage::fake('local');
        $service = app(FileService::class);
        $file = $service->upload(UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf'));
        $product = Product::query()->create(['product_name' => 'T-shirt']);

        $this->assertNull($file->model_type);
        $this->assertNull($file->model_id);

        $file = $service->attach($file, $product);

        $this->assertSame(Product::class, $file->model_type);
        $this->assertSame($product->id, $file->model_id);
    }

    public function test_deleting_a_file_record_also_deletes_the_stored_file(): void
    {
        Storage::fake('local');
        $service = app(FileService::class);
        $file = $service->upload(UploadedFile::fake()->create('manual.pdf', 100, 'application/pdf'));

        $service->delete($file);

        Storage::disk('local')->assertMissing($file->path);
        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }

    public function test_it_stores_an_external_video_as_a_polymorphic_file_record(): void
    {
        $product = Product::query()->create(['product_name' => 'T-shirt']);

        $file = app(FileService::class)->createExternalUrl(
            $product,
            'https://www.youtube.com/watch?v=example',
            ['sort_order' => 2]
        );

        $this->assertSame('https://www.youtube.com/watch?v=example', $file->external_url);
        $this->assertSame('video', $file->type);
        $this->assertSame(2, $file->sort_order);
        $this->assertNull($file->path);
        $this->assertSame(Product::class, $file->model_type);
        $this->assertSame($product->id, $file->model_id);
    }

    public function test_it_replaces_a_file_without_changing_its_id_or_owner(): void
    {
        Storage::fake('public');
        $product = Product::query()->create(['product_name' => 'T-shirt']);
        $service = app(FileService::class);
        $file = $service->upload(
            UploadedFile::fake()->image('old.jpg'),
            $product,
            ['disk' => 'public', 'directory' => 'products/1']
        );
        $oldPath = $file->path;

        $replaced = $service->replace($file, UploadedFile::fake()->image('new.png'));

        $this->assertSame($file->id, $replaced->id);
        $this->assertSame($product->id, $replaced->model_id);
        Storage::disk('public')->assertMissing($oldPath);
        Storage::disk('public')->assertExists($replaced->path);
    }
}

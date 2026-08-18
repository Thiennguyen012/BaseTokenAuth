<?php

namespace Tests\Feature;

use App\Models\Products\Product;
use App\Services\Product\ProductService;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uploads_images_when_creating_a_product(): void
    {
        Storage::fake('public');

        $product = app(ProductService::class)->create([
            'product_name' => 'T-shirt',
            'images' => [
                UploadedFile::fake()->image('front.jpg'),
                UploadedFile::fake()->image('back.png'),
            ],
        ]);

        $this->assertCount(2, $product->files);
        $resource = (new ProductResource($product))->toArray(Request::create('/'));
        $this->assertSame($product->files->first()->id, $resource['first_image']->resource->id);
        $product->files->each(function ($file) use ($product): void {
            $this->assertSame(Product::class, $file->model_type);
            $this->assertSame($product->id, $file->model_id);
            $this->assertSame('image', $file->type);
            Storage::disk('public')->assertExists($file->path);
        });
    }

    public function test_it_appends_images_when_updating_a_product(): void
    {
        Storage::fake('public');
        $service = app(ProductService::class);
        $product = $service->create([
            'product_name' => 'T-shirt',
            'images' => [UploadedFile::fake()->image('front.jpg')],
        ]);

        $product = $service->update($product, [
            'images' => [UploadedFile::fake()->image('back.jpg')],
        ]);

        $this->assertCount(2, $product->files);
        $this->assertEqualsCanonicalizing(
            ['front.jpg', 'back.jpg'],
            $product->files->pluck('file_name')->all()
        );
    }
}

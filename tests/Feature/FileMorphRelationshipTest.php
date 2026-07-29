<?php

namespace Tests\Feature;

use App\Models\Files\File;
use App\Models\Products\Product;
use App\Models\ProductVariants\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FileMorphRelationshipTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_file_can_belong_to_a_product(): void
    {
        $product = Product::query()->create(['product_name' => 'T-shirt']);
        $file = $product->files()->create([
            'title' => 'Front image',
            'file_name' => 'front.jpg',
            'path' => 'products/t-shirt/front.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'type' => 'image',
        ]);

        $this->assertInstanceOf(Product::class, $file->model);
        $this->assertSame($product->id, $file->model->id);
        $this->assertSame('products/t-shirt/front.jpg', $product->files->first()->path);
    }

    public function test_a_file_can_belong_to_a_product_variant(): void
    {
        $product = Product::query()->create(['product_name' => 'T-shirt']);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'TS-RED-M',
            'combination_key' => hash('sha256', '1:2'),
        ]);

        $file = $variant->files()->create([
            'title' => 'Variant image',
            'file_name' => 'ts-red-m.jpg',
            'path' => 'variants/ts-red-m.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'type' => 'image',
        ]);

        $this->assertInstanceOf(ProductVariant::class, $file->model);
        $this->assertSame($variant->id, $file->model->id);
        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'model_type' => ProductVariant::class,
            'model_id' => $variant->id,
        ]);
    }

    public function test_deleting_an_owner_deletes_its_file_records(): void
    {
        $product = Product::query()->create(['product_name' => 'T-shirt']);
        $file = $product->files()->create([
            'title' => 'Front image',
            'path' => 'products/t-shirt/front.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
        ]);

        $product->forceDelete();

        $this->assertDatabaseMissing('files', ['id' => $file->id]);
    }
}

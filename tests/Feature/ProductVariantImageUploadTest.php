<?php

namespace Tests\Feature;

use App\Models\ProductVariants\ProductVariant;
use App\Models\Products\Product;
use App\Models\Products\ProductVariantGroup;
use App\Models\Variants\VariantGroup;
use App\Services\ProductVariant\ProductVariantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductVariantImageUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_uploads_images_when_creating_a_product_variant(): void
    {
        Storage::fake('public');
        [$product, $option] = $this->catalog();

        $variant = app(ProductVariantService::class)->create([
            'product_id' => $product->id,
            'sku' => 'TS-RED',
            'option_ids' => [$option->id],
            'images' => [UploadedFile::fake()->image('red.jpg')],
        ]);

        $this->assertCount(1, $variant->files);
        $file = $variant->files->first();
        $this->assertSame(ProductVariant::class, $file->model_type);
        $this->assertSame($variant->id, $file->model_id);
        $this->assertSame('image', $file->type);
        Storage::disk('public')->assertExists($file->path);
    }

    public function test_it_appends_images_when_updating_a_product_variant(): void
    {
        Storage::fake('public');
        [$product, $option] = $this->catalog();
        $service = app(ProductVariantService::class);
        $variant = $service->create([
            'product_id' => $product->id,
            'sku' => 'TS-RED',
            'option_ids' => [$option->id],
            'images' => [UploadedFile::fake()->image('front.jpg')],
        ]);

        $variant = $service->update($variant, [
            'images' => [UploadedFile::fake()->image('back.jpg')],
        ]);

        $this->assertCount(2, $variant->files);
        $this->assertEqualsCanonicalizing(
            ['front.jpg', 'back.jpg'],
            $variant->files->pluck('file_name')->all()
        );
    }

    private function catalog(): array
    {
        $product = Product::query()->create(['product_name' => 'T-shirt']);
        $group = VariantGroup::query()->create(['group_code' => 'color', 'group_name' => 'Color']);
        $configuration = ProductVariantGroup::query()->create([
            'product_id' => $product->id,
            'variant_group_id' => $group->id,
            'is_required' => true,
        ]);
        $option = $configuration->options()->create(['option_code' => 'red', 'option_name' => 'Red']);

        return [$product, $option];
    }
}

<?php

namespace Tests\Feature;

use App\Models\Products\Product;
use App\Models\Variants\VariantGroup;
use App\Models\Variants\VariantOption;
use App\Services\ProductVariant\ProductVariantService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ProductVariantServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_complete_variant_and_rejects_duplicate_combination(): void
    {
        [$product, $colorRed, $sizeM] = $this->catalog();
        $service = app(ProductVariantService::class);

        $variant = $service->create([
            'product_id' => $product->id,
            'sku' => 'TS-RED-M',
            'price' => 100,
            'stock' => 5,
            'option_ids' => [$sizeM->id, $colorRed->id],
        ]);

        $this->assertCount(2, $variant->options);
        $this->assertDatabaseHas('product_variants', ['sku' => 'TS-RED-M']);

        $this->expectException(ValidationException::class);
        $service->create([
            'product_id' => $product->id,
            'sku' => 'TS-RED-M-2',
            'option_ids' => [$colorRed->id, $sizeM->id],
        ]);
    }

    public function test_it_rejects_a_variant_missing_a_required_group(): void
    {
        [$product, $colorRed] = $this->catalog();

        $this->expectException(ValidationException::class);
        app(ProductVariantService::class)->create([
            'product_id' => $product->id,
            'sku' => 'TS-RED',
            'option_ids' => [$colorRed->id],
        ]);
    }

    public function test_it_rejects_an_option_from_an_unconfigured_group(): void
    {
        [$product, $colorRed, $sizeM] = $this->catalog();
        $material = VariantGroup::query()->create(['group_code' => 'material', 'group_name' => 'Material']);
        $cotton = $material->options()->create(['option_code' => 'cotton', 'option_name' => 'Cotton']);

        $this->expectException(ValidationException::class);
        app(ProductVariantService::class)->create([
            'product_id' => $product->id,
            'sku' => 'TS-INVALID',
            'option_ids' => [$colorRed->id, $sizeM->id, $cotton->id],
        ]);
    }

    public function test_product_variant_groups_follow_pivot_sort_order(): void
    {
        $product = Product::query()->create(['product_name' => 'T-shirt']);
        $color = VariantGroup::query()->create(['group_code' => 'color', 'group_name' => 'Color']);
        $size = VariantGroup::query()->create(['group_code' => 'size', 'group_name' => 'Size']);

        $product->variantGroups()->attach([
            $color->id => ['sort_order' => 20],
            $size->id => ['sort_order' => 10],
        ]);

        $this->assertSame(
            [$size->id, $color->id],
            $product->fresh()->variantGroups->pluck('id')->all()
        );
    }

    public function test_it_lists_finds_updates_and_deletes_variants(): void
    {
        [$product, $colorRed, $sizeM] = $this->catalog();
        $service = app(ProductVariantService::class);
        $variant = $service->create([
            'product_id' => $product->id,
            'sku' => 'TS-RED-M',
            'price' => 100,
            'stock' => 5,
            'option_ids' => [$colorRed->id, $sizeM->id],
        ]);

        $listed = $service->paginate(10, 'RED', $product->id);
        $this->assertSame([$variant->id], $listed->pluck('id')->all());
        $this->assertTrue($listed->first()->relationLoaded('options'));
        $this->assertSame($variant->id, $service->find($variant->id)->id);

        $updated = $service->update($variant, [
            'sku' => 'TS-RED-M-NEW',
            'price' => 120,
            'stock' => 8,
            'is_active' => false,
        ]);

        $this->assertSame('TS-RED-M-NEW', $updated->sku);
        $this->assertSame('120.00', $updated->price);
        $this->assertSame(8, $updated->stock);
        $this->assertFalse($updated->is_active);
        $this->assertCount(2, $updated->options);

        $service->delete($updated);

        $this->assertDatabaseMissing('product_variants', ['id' => $variant->id]);
        $this->assertDatabaseMissing('product_variant_values', ['product_variant_id' => $variant->id]);
    }

    private function catalog(): array
    {
        $product = Product::query()->create(['product_name' => 'T-shirt']);
        $color = VariantGroup::query()->create(['group_code' => 'color', 'group_name' => 'Color']);
        $size = VariantGroup::query()->create(['group_code' => 'size', 'group_name' => 'Size']);
        $colorRed = $color->options()->create(['option_code' => 'red', 'option_name' => 'Red']);
        $sizeM = $size->options()->create(['option_code' => 'm', 'option_name' => 'M']);
        $product->variantGroups()->attach([
            $color->id => ['is_required' => true],
            $size->id => ['is_required' => true],
        ]);

        return [$product, $colorRed, $sizeM];
    }
}

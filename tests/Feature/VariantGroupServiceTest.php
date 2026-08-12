<?php

namespace Tests\Feature;

use App\Models\Products\Product;
use App\Models\Products\ProductVariantGroup;
use App\Models\Variants\VariantGroup;
use App\Services\VariantGroup\VariantGroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariantGroupServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_lists_finds_and_updates_a_global_group_definition(): void
    {
        $service = app(VariantGroupService::class);
        $group = $service->create(['group_code' => 'color', 'group_name' => 'Color']);

        $this->assertDatabaseHas('variant_groups', ['id' => $group->id]);
        $this->assertSame([$group->id], $service->paginate(10)->pluck('id')->all());
        $this->assertSame($group->id, $service->find($group->id)->id);

        $updated = $service->update($group, ['group_name' => 'Màu sắc']);
        $this->assertSame('Màu sắc', $updated->group_name);
    }

    public function test_options_belong_to_a_product_group_configuration(): void
    {
        $product = Product::query()->create(['product_name' => 'T-shirt']);
        $group = VariantGroup::query()->create(['group_code' => 'size', 'group_name' => 'Size']);
        $configuration = ProductVariantGroup::query()->create([
            'product_id' => $product->id,
            'variant_group_id' => $group->id,
        ]);
        $option = $configuration->options()->create(['option_code' => 's', 'option_name' => 'S']);

        $this->assertSame($configuration->id, $option->product_variant_group_id);
        $this->assertSame($product->id, $option->productVariantGroup->product_id);
        $this->assertSame($group->id, $option->productVariantGroup->variant_group_id);
    }

    public function test_it_counts_products_using_a_variant_group(): void
    {
        $group = VariantGroup::query()->create(['group_code' => 'color', 'group_name' => 'Color']);
        foreach (['A', 'B'] as $name) {
            $product = Product::query()->create(['product_name' => "Product {$name}"]);
            ProductVariantGroup::query()->create(['product_id' => $product->id, 'variant_group_id' => $group->id]);
        }

        $this->assertSame(2, app(VariantGroupService::class)->usageCount($group->id));
        $this->assertNull(app(VariantGroupService::class)->usageCount(999999));
    }

    public function test_deleting_a_group_deletes_product_specific_options(): void
    {
        $product = Product::query()->create(['product_name' => 'T-shirt']);
        $group = VariantGroup::query()->create(['group_code' => 'color', 'group_name' => 'Color']);
        $configuration = ProductVariantGroup::query()->create(['product_id' => $product->id, 'variant_group_id' => $group->id]);
        $option = $configuration->options()->create(['option_code' => 'red', 'option_name' => 'Red']);

        app(VariantGroupService::class)->delete($group);

        $this->assertDatabaseMissing('variant_groups', ['id' => $group->id]);
        $this->assertDatabaseMissing('variant_options', ['id' => $option->id]);
    }
}

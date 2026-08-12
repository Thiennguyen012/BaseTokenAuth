<?php

namespace Tests\Feature;

use App\Models\Variants\VariantGroup;
use App\Models\Products\Product;
use App\Models\Products\ProductVariantGroup;
use App\Services\VariantOption\VariantOptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariantOptionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_paginates_and_filters_options_by_group(): void
    {
        $color = VariantGroup::query()->create(['group_code' => 'color', 'group_name' => 'Color']);
        $size = VariantGroup::query()->create(['group_code' => 'size', 'group_name' => 'Size']);
        $product = Product::query()->create(['product_name' => 'Product']);
        $colorConfiguration = ProductVariantGroup::query()->create(['product_id' => $product->id, 'variant_group_id' => $color->id]);
        $sizeConfiguration = ProductVariantGroup::query()->create(['product_id' => $product->id, 'variant_group_id' => $size->id]);
        $blue = $colorConfiguration->options()->create([
            'option_code' => 'blue',
            'option_name' => 'Blue',
            'sort_order' => 20,
        ]);
        $red = $colorConfiguration->options()->create([
            'option_code' => 'red',
            'option_name' => 'Red',
            'sort_order' => 10,
        ]);
        $sizeConfiguration->options()->create(['option_code' => 'm', 'option_name' => 'M']);

        $result = app(VariantOptionService::class)->paginate(10, '', $colorConfiguration->id);

        $this->assertSame([$red->id, $blue->id], $result->pluck('id')->all());
    }

    public function test_it_finds_updates_and_deletes_an_option(): void
    {
        $group = VariantGroup::query()->create(['group_code' => 'color', 'group_name' => 'Color']);
        $product = Product::query()->create(['product_name' => 'Product']);
        $configuration = ProductVariantGroup::query()->create(['product_id' => $product->id, 'variant_group_id' => $group->id]);
        $option = $configuration->options()->create(['option_code' => 'red', 'option_name' => 'Red']);
        $service = app(VariantOptionService::class);

        $this->assertSame($option->id, $service->find($option->id)->id);

        $updated = $service->update($option, [
            'option_name' => 'Dark red',
            'sort_order' => 5,
            'is_active' => false,
        ]);

        $this->assertSame('Dark red', $updated->option_name);
        $this->assertSame(5, $updated->sort_order);
        $this->assertFalse($updated->is_active);

        $service->delete($updated);

        $this->assertDatabaseMissing('variant_options', ['id' => $option->id]);
    }
}

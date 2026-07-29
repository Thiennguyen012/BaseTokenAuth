<?php

namespace Tests\Feature;

use App\Models\Variants\VariantGroup;
use App\Services\VariantGroup\VariantGroupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariantGroupServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_a_group_without_assigning_options_when_option_ids_is_missing(): void
    {
        $group = app(VariantGroupService::class)->create([
            'group_code' => 'color',
            'group_name' => 'Color',
        ]);

        $this->assertDatabaseHas('variant_groups', ['id' => $group->id]);
        $this->assertCount(0, $group->options);
    }

    public function test_it_creates_a_group_and_assigns_existing_options(): void
    {
        $oldGroup = VariantGroup::query()->create([
            'group_code' => 'old',
            'group_name' => 'Old group',
        ]);
        $red = $oldGroup->options()->create(['option_code' => 'red', 'option_name' => 'Red']);
        $blue = $oldGroup->options()->create(['option_code' => 'blue', 'option_name' => 'Blue']);

        $newGroup = app(VariantGroupService::class)->create([
            'group_code' => 'color',
            'group_name' => 'Color',
            'option_ids' => [$red->id, $blue->id],
        ]);

        $this->assertEqualsCanonicalizing([$red->id, $blue->id], $newGroup->options->pluck('id')->all());
        $this->assertDatabaseHas('variant_options', ['id' => $red->id, 'variant_group_id' => $newGroup->id]);
        $this->assertDatabaseHas('variant_options', ['id' => $blue->id, 'variant_group_id' => $newGroup->id]);
    }

    public function test_it_paginates_groups_with_options_in_name_order(): void
    {
        $second = VariantGroup::query()->create([
            'group_code' => 'size',
            'group_name' => 'Size',
        ]);
        $first = VariantGroup::query()->create([
            'group_code' => 'color',
            'group_name' => 'Color',
        ]);
        $first->options()->create(['option_code' => 'red', 'option_name' => 'Red']);

        $groups = app(VariantGroupService::class)->paginate(10);

        $this->assertSame([$first->id, $second->id], $groups->pluck('id')->all());
        $this->assertTrue($groups->first()->relationLoaded('options'));
    }

    public function test_it_finds_a_group_with_options(): void
    {
        $group = VariantGroup::query()->create([
            'group_code' => 'color',
            'group_name' => 'Color',
        ]);
        $option = $group->options()->create(['option_code' => 'red', 'option_name' => 'Red']);

        $result = app(VariantGroupService::class)->find($group->id);

        $this->assertNotNull($result);
        $this->assertTrue($result->relationLoaded('options'));
        $this->assertSame([$option->id], $result->options->pluck('id')->all());
    }

    public function test_it_updates_a_group_and_assigns_options(): void
    {
        $group = VariantGroup::query()->create([
            'group_code' => 'colour',
            'group_name' => 'Colour',
        ]);
        $otherGroup = VariantGroup::query()->create([
            'group_code' => 'other',
            'group_name' => 'Other',
        ]);
        $option = $otherGroup->options()->create([
            'option_code' => 'red',
            'option_name' => 'Red',
        ]);

        $result = app(VariantGroupService::class)->update($group, [
            'group_code' => 'color',
            'group_name' => 'Color',
            'option_ids' => [$option->id],
        ]);

        $this->assertSame('color', $result->group_code);
        $this->assertSame('Color', $result->group_name);
        $this->assertSame($group->id, $option->fresh()->variant_group_id);
    }

    public function test_it_deletes_a_group_and_its_options(): void
    {
        $group = VariantGroup::query()->create([
            'group_code' => 'color',
            'group_name' => 'Color',
        ]);
        $option = $group->options()->create([
            'option_code' => 'red',
            'option_name' => 'Red',
        ]);

        app(VariantGroupService::class)->delete($group);

        $this->assertDatabaseMissing('variant_groups', ['id' => $group->id]);
        $this->assertDatabaseMissing('variant_options', ['id' => $option->id]);
    }
}

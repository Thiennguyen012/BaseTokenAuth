<?php

namespace App\Services\VariantGroup;

use App\Repositories\VariantGroup\VariantGroupInterface;
use App\Models\Variants\VariantOption;
use Illuminate\Support\Facades\DB;

class VariantGroupService
{
    public function __construct(protected VariantGroupInterface $variantGroupRepository) {}

    public function paginate(int $limit = 10, string $search = '')
    {
        $where = [];

        if ($search !== '') {
            $where['orWhere'] = [
                'group_code' => ['group_code', 'like', $search],
                'group_name' => ['group_name', 'like', $search],
            ];
        }

        return $this->variantGroupRepository->paginate(
            $where,
            ['group_name' => 'asc', 'id' => 'asc'],
            ['*'],
            ['options'],
            $limit
        );
    }

    public function find($id)
    {
        return $this->variantGroupRepository->first(
            ['id' => $id],
            [],
            ['*'],
            ['options']
        );
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $optionIds = $data['option_ids'] ?? [];
            unset($data['option_ids']);

            $group = $this->variantGroupRepository->create($data);

            if (!empty($optionIds)) {
                VariantOption::query()
                    ->whereIn('id', $optionIds)
                    ->update(['variant_group_id' => $group->id]);
            }

            return $group->load('options');
        });
    }

    public function update($group, array $data)
    {
        return DB::transaction(function () use ($group, $data) {
            $hasOptionIds = array_key_exists('option_ids', $data);
            $optionIds = $data['option_ids'] ?? [];
            unset($data['option_ids']);

            $group = $this->variantGroupRepository->edit($group, $data);

            if ($hasOptionIds && !empty($optionIds)) {
                VariantOption::query()
                    ->whereIn('id', $optionIds)
                    ->update(['variant_group_id' => $group->id]);
            }

            return $group->load('options');
        });
    }

    public function delete($group)
    {
        return $this->variantGroupRepository->delete($group);
    }
}

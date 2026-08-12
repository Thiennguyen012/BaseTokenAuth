<?php

namespace App\Services\VariantGroup;

use App\Repositories\VariantGroup\VariantGroupInterface;
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
            [],
            $limit
        );
    }

    public function find($id)
    {
        return $this->variantGroupRepository->first(
            ['id' => $id],
            [],
            ['*'],
            []
        );
    }

    public function usageCount($id): ?int
    {
        $group = $this->variantGroupRepository->find($id);

        return $group ? $group->products()->count() : null;
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            unset($data['option_ids']);
            return $this->variantGroupRepository->create($data);
        });
    }

    public function update($group, array $data)
    {
        return DB::transaction(function () use ($group, $data) {
            unset($data['option_ids']);
            return $this->variantGroupRepository->edit($group, $data);
        });
    }

    public function delete($group)
    {
        return DB::transaction(function () use ($group) {
            $group->productConfigurations()->delete();
            return $this->variantGroupRepository->delete($group);
        });
    }
}

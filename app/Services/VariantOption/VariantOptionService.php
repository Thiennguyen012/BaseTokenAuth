<?php

namespace App\Services\VariantOption;

use App\Repositories\VariantOption\VariantOptionInterface;

class VariantOptionService
{
    public function __construct(protected VariantOptionInterface $variantOptionRepository) {}

    public function paginate(int $limit = 10, string $search = '', ?int $variantGroupId = null)
    {
        $where = [];

        if ($search !== '') {
            $where['orWhere'] = [
                'option_code' => ['option_code', 'like', $search],
                'option_name' => ['option_name', 'like', $search],
            ];
        }

        if ($variantGroupId !== null) {
            $where['variant_group_id'] = $variantGroupId;
        }

        return $this->variantOptionRepository->paginate(
            $where,
            ['sort_order' => 'asc', 'option_name' => 'asc', 'id' => 'asc'],
            ['*'],
            [],
            $limit
        );
    }

    public function find($id)
    {
        return $this->variantOptionRepository->find($id);
    }

    public function create(array $data)
    {
        return $this->variantOptionRepository->create($data);
    }

    public function update($variantOption, array $data)
    {
        return $this->variantOptionRepository->edit($variantOption, $data);
    }

    public function delete($variantOption)
    {
        return $this->variantOptionRepository->delete($variantOption);
    }
}

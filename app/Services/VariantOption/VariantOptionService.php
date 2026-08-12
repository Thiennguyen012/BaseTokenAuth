<?php

namespace App\Services\VariantOption;

use App\Repositories\VariantOption\VariantOptionInterface;
use Illuminate\Validation\ValidationException;

class VariantOptionService
{
    public function __construct(protected VariantOptionInterface $variantOptionRepository) {}

    public function paginate(int $limit = 10, string $search = '', ?int $configurationId = null)
    {
        $where = [];

        if ($search !== '') {
            $where['orWhere'] = [
                'option_code' => ['option_code', 'like', $search],
                'option_name' => ['option_name', 'like', $search],
            ];
        }

        if ($configurationId !== null) {
            $where['product_variant_group_id'] = $configurationId;
        }

        return $this->variantOptionRepository->paginate(
            $where,
            ['sort_order' => 'asc', 'option_name' => 'asc', 'id' => 'asc'],
            ['*'],
            ['productVariantGroup.group'],
            $limit
        );
    }

    public function find($id)
    {
        return $this->variantOptionRepository->find($id)?->load('productVariantGroup.group');
    }

    public function create(array $data)
    {
        return $this->variantOptionRepository->create($data)->load('productVariantGroup.group');
    }

    public function update($variantOption, array $data)
    {
        return $this->variantOptionRepository->edit($variantOption, $data)->load('productVariantGroup.group');
    }

    public function delete($variantOption)
    {
        if ($variantOption->productVariants()->exists()) {
            throw ValidationException::withMessages([
                'option' => 'Giá trị biến thể đang được sử dụng, không thể xóa.',
            ]);
        }

        return $this->variantOptionRepository->delete($variantOption);
    }
}
